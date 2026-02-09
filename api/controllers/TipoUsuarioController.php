<?php
/**
 * Controlador TipoUsuario
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/TipoUsuario.php';
require_once __DIR__ . '/../utils/Response.php';

class TipoUsuarioController {

    /**
     * Listar todos los tipos de usuario
     * GET /api/routes/tipos-usuario.php?action=list
     */
    public function listAll() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                return;
            }

            $tipoUsuario = new TipoUsuario();
            $tipos = $tipoUsuario->readAll();

            Response::success('Tipos de usuario obtenidos', 200, [
                'tipos' => $tipos
            ]);

        } catch (Exception $e) {
            error_log("Error en listAll: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
}
?>
