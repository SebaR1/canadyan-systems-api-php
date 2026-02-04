<?php
/**
 * Servicio de Email
 * Canadian Sistemas API
 * Manejo de envío de emails usando PHPMailer
 */

require_once __DIR__ . '/../vendor/autoload.php';  // Desde api/services/ sube 1 nivel

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $fromEmail;
    private $fromName;
    private $contactEmailTo;
    
    public function __construct() {
        // Cargar variables del .env
        $this->loadEnv();
        
        // Configurar PHPMailer
        $this->mailer = new PHPMailer(true);
        
        try {
            // Configuración del servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host       = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password   = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port       = $_ENV['SMTP_PORT'];
            $this->mailer->CharSet    = 'UTF-8';
            
            // Configurar remitente
            $this->fromEmail = $_ENV['SMTP_FROM_EMAIL'];
            $this->fromName = $_ENV['SMTP_FROM_NAME'];
            $this->contactEmailTo = $_ENV['CONTACT_EMAIL_TO'];
            
            // Debug (descomentar para desarrollo)
            // $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            
        } catch (Exception $e) {
            error_log("Error configurando EmailService: " . $e->getMessage());
        }
    }
    
    /**
     * Cargar variables del .env
     */
    private function loadEnv() {
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) {
            error_log("Archivo .env no encontrado en: " . $envPath);
            return;
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
    
    /**
     * Enviar email de notificación al administrador
     */
    public function sendContactNotification($data) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Remitente
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            // Destinatario (admin)
            $this->mailer->addAddress($this->contactEmailTo);
            
            // Reply-to (para que admin pueda responder directamente)
            $this->mailer->addReplyTo($data['correo_electronico'], $data['nombre_apellido']);
            
            // Asunto
            $this->mailer->Subject = 'Nuevo mensaje de contacto - ' . $data['razon_social_empresa'];
            
            // Contenido HTML
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getAdminEmailTemplate($data);
            $this->mailer->AltBody = $this->getAdminEmailPlainText($data);
            
            // Enviar
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email enviado exitosamente al admin: " . $this->contactEmailTo);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error enviando email al admin: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Enviar email de confirmación al cliente
     */
    public function sendContactConfirmation($data) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Remitente
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            // Destinatario (cliente)
            $this->mailer->addAddress($data['correo_electronico'], $data['nombre_apellido']);
            
            // Asunto
            $this->mailer->Subject = 'Confirmación de mensaje recibido - Canadian Sistemas';
            
            // Contenido HTML
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getClientEmailTemplate($data);
            $this->mailer->AltBody = $this->getClientEmailPlainText($data);
            
            // Enviar
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email de confirmación enviado a: " . $data['correo_electronico']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error enviando confirmación al cliente: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Plantilla HTML para email al administrador
     */
    private function getAdminEmailTemplate($data) {
        $fecha = date('d/m/Y H:i');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f4f4f4; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: #ffffff; 
                    border-radius: 8px; 
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header { 
                    background: #333030; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 { 
                    color: #ffffff; 
                    margin: 0; 
                    font-size: 24px; 
                }
                .content { 
                    padding: 30px 20px; 
                }
                .alert-box {
                    background: #FFF7ED;
                    border-left: 4px solid #F97316;
                    padding: 15px;
                    margin-bottom: 25px;
                }
                .alert-box p {
                    margin: 0;
                    color: #9A3412;
                    font-weight: 600;
                }
                .info-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 20px 0; 
                }
                .info-table td { 
                    padding: 12px; 
                    border-bottom: 1px solid #e5e7eb; 
                }
                .info-table td:first-child { 
                    font-weight: 600; 
                    color: #6b7280; 
                    width: 40%; 
                }
                .message-box {
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    padding: 15px;
                    margin: 20px 0;
                }
                .message-box h3 {
                    margin: 0 0 10px 0;
                    color: #374151;
                    font-size: 16px;
                }
                .message-content {
                    color: #4b5563;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }
                .footer { 
                    background: #f9fafb; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 12px; 
                    color: #6b7280; 
                }
                .btn-reply {
                    display: inline-block;
                    background: #F97316;
                    color: #ffffff !important;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 6px;
                    margin: 20px 0;
                    font-weight: 600;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📧 Nuevo Mensaje de Contacto</h1>
                </div>
                
                <div class='content'>
                    <div class='alert-box'>
                        <p>Has recibido un nuevo mensaje de contacto</p>
                    </div>
                    
                    <table class='info-table'>
                        <tr>
                            <td>Nombre y Apellido:</td>
                            <td><strong>{$data['nombre_apellido']}</strong></td>
                        </tr>
                        <tr>
                            <td>Empresa:</td>
                            <td><strong>{$data['razon_social_empresa']}</strong></td>
                        </tr>
                        <tr>
                            <td>CUIT:</td>
                            <td>{$data['cuit']}</td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><a href='mailto:{$data['correo_electronico']}' style='color: #F97316;'>{$data['correo_electronico']}</a></td>
                        </tr>
                        <tr>
                            <td>Celular:</td>
                            <td>{$data['celular']}</td>
                        </tr>
                        <tr>
                            <td>Localidad:</td>
                            <td>{$data['localidad']}</td>
                        </tr>
                        <tr>
                            <td>Fecha:</td>
                            <td>{$fecha}</td>
                        </tr>
                    </table>
                    
                    <div class='message-box'>
                        <h3>Mensaje:</h3>
                        <div class='message-content'>{$data['mensaje']}</div>
                    </div>
                    
                    <center>
                        <a href='mailto:{$data['correo_electronico']}' class='btn-reply'>Responder al Cliente</a>
                    </center>
                </div>
                
                <div class='footer'>
                    <p><strong>Canadian Sistemas</strong> - Seguridad y Control</p>
                    <p>Este es un mensaje automático del sistema de contacto web</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Plantilla HTML para email de confirmación al cliente
     */
    private function getClientEmailTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f4f4f4; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: #ffffff; 
                    border-radius: 8px; 
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header { 
                    background: #333030; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 { 
                    color: #ffffff; 
                    margin: 0; 
                    font-size: 24px; 
                }
                .content { 
                    padding: 30px 20px; 
                }
                .success-box {
                    background: #ECFDF5;
                    border-left: 4px solid #10B981;
                    padding: 15px;
                    margin-bottom: 25px;
                    border-radius: 4px;
                }
                .success-box p {
                    margin: 0;
                    color: #065F46;
                    font-weight: 600;
                }
                .greeting {
                    font-size: 18px;
                    color: #374151;
                    margin-bottom: 15px;
                }
                .text-block {
                    color: #4b5563;
                    margin: 15px 0;
                }
                .highlight-box {
                    background: #FFF7ED;
                    border: 2px solid #F97316;
                    border-radius: 6px;
                    padding: 20px;
                    margin: 25px 0;
                    text-align: center;
                }
                .highlight-box h3 {
                    color: #F97316;
                    margin: 0 0 10px 0;
                    font-size: 18px;
                }
                .contact-info {
                    background: #f9fafb;
                    border-radius: 6px;
                    padding: 20px;
                    margin: 25px 0;
                }
                .contact-info h4 {
                    margin: 0 0 15px 0;
                    color: #374151;
                }
                .contact-item {
                    margin: 10px 0;
                    color: #4b5563;
                }
                .contact-item a {
                    color: #F97316;
                    text-decoration: none;
                }
                .footer { 
                    background: #f9fafb; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 12px; 
                    color: #6b7280; 
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓ Mensaje Recibido</h1>
                </div>
                
                <div class='content'>
                    <div class='success-box'>
                        <p>¡Tu mensaje ha sido recibido exitosamente!</p>
                    </div>
                    
                    <p class='greeting'>Hola <strong>{$data['nombre_apellido']}</strong>,</p>
                    
                    <p class='text-block'>
                        Gracias por comunicarte con <strong>Canadian Sistemas</strong>. 
                        Hemos recibido tu mensaje y nuestro equipo lo revisará a la brevedad.
                    </p>
                    
                    <div class='highlight-box'>
                        <h3>⏱️ Tiempo de respuesta estimado</h3>
                        <p style='color: #6b7280; margin: 0;'>
                            Te responderemos en un plazo máximo de <strong>24 a 48 horas hábiles</strong>
                        </p>
                    </div>
                    
                    <p class='text-block'>
                        Tu consulta es importante para nosotros y nos aseguraremos de brindarte 
                        la mejor atención posible.
                    </p>
                    
                    <div class='contact-info'>
                        <h4>📞 ¿Necesitas ayuda urgente?</h4>
                        <div class='contact-item'>
                            <strong>Teléfono:</strong> <a href='tel:+541170093111'>+54 11 7009-3111</a>
                        </div>
                        <div class='contact-item'>
                            <strong>Email:</strong> <a href='mailto:ventas@canadian.com.ar'>ventas@canadian.com.ar</a>
                        </div>
                    </div>
                    
                    <p class='text-block' style='font-size: 14px; color: #6b7280;'>
                        <strong>Nota:</strong> Este es un email automático, por favor no respondas a este mensaje.
                        Nos comunicaremos contigo a través de tu email: <strong>{$data['correo_electronico']}</strong>
                    </p>
                </div>
                
                <div class='footer'>
                    <p><strong>Canadian Sistemas</strong></p>
                    <p>Seguridad y Control</p>
                    <p style='margin-top: 10px;'>
                        <a href='mailto:ventas@canadian.com.ar' style='color: #F97316; text-decoration: none;'>ventas@canadian.com.ar</a> | 
                        <a href='tel:+541170093111' style='color: #F97316; text-decoration: none;'>+54 11 7009-3111</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Enviar email de recuperación de contraseña
     */
    public function sendPasswordResetEmail($email, $nombre, $token) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($email, $nombre);

            $this->mailer->Subject = 'Recuperación de contraseña - Canadian Sistemas';

            $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:3000/canadian-sistemas';
            $resetLink = rtrim($appUrl, '/') . '/reset-password?token=' . $token;

            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getPasswordResetTemplate($nombre, $resetLink);
            $this->mailer->AltBody = $this->getPasswordResetPlainText($nombre, $resetLink);

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Error enviando email de reset: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Plantilla HTML para email de recuperación de contraseña
     */
    private function getPasswordResetTemplate($nombre, $resetLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: #333030; padding: 30px 20px; text-align: center; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
                .content { padding: 30px 20px; }
                .alert-box { background: #FFF7ED; border-left: 4px solid #F97316; padding: 15px; margin-bottom: 25px; border-radius: 4px; }
                .alert-box p { margin: 0; color: #9A3412; font-weight: 600; }
                .text-block { color: #4b5563; margin: 15px 0; }
                .btn-reset { display: inline-block; background: #F97316; color: #ffffff !important; padding: 14px 36px; text-decoration: none; border-radius: 6px; margin: 25px 0; font-weight: 600; font-size: 16px; }
                .token-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px 20px; margin: 20px 0; text-align: center; }
                .token-box p { margin: 0; color: #6b7280; font-size: 13px; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Recuperación de Contraseña</h1>
                </div>
                <div class='content'>
                    <div class='alert-box'>
                        <p>Se ha solicitado recuperar tu contraseña</p>
                    </div>
                    <p class='text-block'>Hola <strong>{$nombre}</strong>,</p>
                    <p class='text-block'>
                        Recibiste esta notificación porque se solicitó un cambio de contraseña para tu cuenta.
                        Si no lo solicitaste tú, puedes ignorar este email.
                    </p>
                    <center>
                        <a href='{$resetLink}' class='btn-reset'>Resetear mi contraseña</a>
                    </center>
                    <div class='token-box'>
                        <p>Este enlace es válido durante <strong>1 hora</strong>.</p>
                        <p>Si el botón no funciona, copia y pega esta URL en tu navegador:</p>
                        <p style='word-break: break-all; color: #F97316; font-size: 12px; margin-top: 8px;'>{$resetLink}</p>
                    </div>
                    <p class='text-block' style='font-size: 13px; color: #9ca3af;'>
                        Por seguridad, este enlace expira en 1 hora y solo puede usarse una vez.
                    </p>
                </div>
                <div class='footer'>
                    <p><strong>Canadian Sistemas</strong> - Seguridad y Control</p>
                    <p>Este es un mensaje automático. No respondas a este email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Texto plano para email de recuperación (fallback)
     */
    private function getPasswordResetPlainText($nombre, $resetLink) {
        return "
RECUPERACIÓN DE CONTRASEÑA - CANADIAN SISTEMAS
================================================

Hola {$nombre},

Se solicitó un cambio de contraseña para tu cuenta.
Si no lo solicitaste tú, puedes ignorar este email.

Para resetear tu contraseña, visita el siguiente enlace:
{$resetLink}

Este enlace es válido durante 1 hora y solo puede usarse una vez.

---
Canadian Sistemas - Seguridad y Control
        ";
    }

    /**
     * Texto plano para email al admin (fallback)
     */
    private function getAdminEmailPlainText($data) {
        $fecha = date('d/m/Y H:i');
        
        return "
NUEVO MENSAJE DE CONTACTO - CANADIAN SISTEMAS
=============================================

Nombre y Apellido: {$data['nombre_apellido']}
Empresa: {$data['razon_social_empresa']}
CUIT: {$data['cuit']}
Email: {$data['correo_electronico']}
Celular: {$data['celular']}
Localidad: {$data['localidad']}
Fecha: {$fecha}

MENSAJE:
{$data['mensaje']}

---------------------------------------------
Para responder, envía un email a: {$data['correo_electronico']}
        ";
    }
    
    /**
     * Texto plano para email al cliente (fallback)
     */
    private function getClientEmailPlainText($data) {
        return "
¡MENSAJE RECIBIDO!

Hola {$data['nombre_apellido']},

Gracias por comunicarte con Canadian Sistemas.

Hemos recibido tu mensaje y nuestro equipo lo revisará a la brevedad.
Te responderemos en un plazo máximo de 24 a 48 horas hábiles.

¿Necesitas ayuda urgente?
- Teléfono: +54 11 7009-3111
- Email: ventas@canadian.com.ar

Nota: Este es un email automático, por favor no respondas a este mensaje.
Nos comunicaremos contigo a través de tu email: {$data['correo_electronico']}

---
Canadian Sistemas
Seguridad y Control
        ";
    }
}
?>