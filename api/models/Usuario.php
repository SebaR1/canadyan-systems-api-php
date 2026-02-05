<?php
/**
 * Modelo Usuario
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";
    
    // Propiedades del objeto
    public $id;
    public $nombre;
    public $apellido;
    public $razon_social_empresa;
    public $cuit;
    public $correo_electronico;
    public $celular;
    public $ciudad;
    public $direccion;
    public $provincia;
    public $cod_imagen;
    public $password;
    public $email_verificado;
    public $tipo_usuario_id;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    
    // Constructor
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, apellido=:apellido, razon_social_empresa=:razon_social_empresa,
                      cuit=:cuit, correo_electronico=:correo_electronico, celular=:celular,
                      ciudad=:ciudad, direccion=:direccion, provincia=:provincia,
                      cod_imagen=:cod_imagen, password=:password, tipo_usuario_id=:tipo_usuario_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->razon_social_empresa = htmlspecialchars(strip_tags($this->razon_social_empresa));
        $this->cuit = htmlspecialchars(strip_tags($this->cuit));
        $this->correo_electronico = htmlspecialchars(strip_tags($this->correo_electronico));
        $this->celular = htmlspecialchars(strip_tags($this->celular));
        $this->ciudad = htmlspecialchars(strip_tags($this->ciudad));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->provincia = htmlspecialchars(strip_tags($this->provincia));
        $this->cod_imagen = htmlspecialchars(strip_tags($this->cod_imagen));
        
        // Hash de la contraseña
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind de valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":razon_social_empresa", $this->razon_social_empresa);
        $stmt->bindParam(":cuit", $this->cuit);
        $stmt->bindParam(":correo_electronico", $this->correo_electronico);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":ciudad", $this->ciudad);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":provincia", $this->provincia);
        $stmt->bindParam(":cod_imagen", $this->cod_imagen);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":tipo_usuario_id", $this->tipo_usuario_id);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Leer usuario por ID
     */
    public function readOne() {
        $query = "SELECT u.id, u.nombre, u.apellido, u.razon_social_empresa, u.cuit,
                         u.correo_electronico, u.celular, u.ciudad, u.direccion, u.provincia,
                         u.cod_imagen, u.email_verificado, u.tipo_usuario_id,
                         tu.nombre as tipo_usuario_nombre, u.created_at, u.updated_at
                  FROM " . $this->table_name . " u
                  LEFT JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id
                  WHERE u.id = :id LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->razon_social_empresa = $row['razon_social_empresa'];
            $this->cuit = $row['cuit'];
            $this->correo_electronico = $row['correo_electronico'];
            $this->celular = $row['celular'];
            $this->ciudad = $row['ciudad'];
            $this->direccion = $row['direccion'];
            $this->provincia = $row['provincia'];
            $this->cod_imagen = $row['cod_imagen'];
            $this->email_verificado = $row['email_verificado'];
            $this->tipo_usuario_id = $row['tipo_usuario_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return $row;
        }
        
        return false;
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail($email) {
        $query = "SELECT u.id, u.nombre, u.apellido, u.razon_social_empresa, u.cuit,
                         u.correo_electronico, u.celular, u.ciudad, u.direccion, u.provincia,
                         u.cod_imagen, u.password, u.email_verificado, u.tipo_usuario_id,
                         tu.nombre as tipo_usuario_nombre, u.created_at, u.updated_at
                  FROM " . $this->table_name . " u
                  LEFT JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id
                  WHERE u.correo_electronico = :email AND u.deleted_at IS NULL LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar usuario por CUIT
     */
    public function findByCuit($cuit) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE cuit = :cuit LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cuit", $cuit);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualizar usuario
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre=:nombre, apellido=:apellido, razon_social_empresa=:razon_social_empresa,
                      cuit=:cuit, correo_electronico=:correo_electronico,
                      celular=:celular, ciudad=:ciudad, direccion=:direccion, provincia=:provincia,
                      cod_imagen=:cod_imagen, tipo_usuario_id=:tipo_usuario_id
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->razon_social_empresa = htmlspecialchars(strip_tags($this->razon_social_empresa));
        $this->cuit = htmlspecialchars(strip_tags($this->cuit));
        $this->correo_electronico = htmlspecialchars(strip_tags($this->correo_electronico));
        $this->celular = htmlspecialchars(strip_tags($this->celular));
        $this->ciudad = htmlspecialchars(strip_tags($this->ciudad));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->provincia = htmlspecialchars(strip_tags($this->provincia));
        $this->cod_imagen = htmlspecialchars(strip_tags($this->cod_imagen));

        // Bind de valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":razon_social_empresa", $this->razon_social_empresa);
        $stmt->bindParam(":cuit", $this->cuit);
        $stmt->bindParam(":correo_electronico", $this->correo_electronico);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":ciudad", $this->ciudad);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":provincia", $this->provincia);
        $stmt->bindParam(":cod_imagen", $this->cod_imagen);
        $stmt->bindParam(":tipo_usuario_id", $this->tipo_usuario_id);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
    
    /**
     * Verificar email
     */
    public function verifyEmail() {
        $query = "UPDATE " . $this->table_name . " SET email_verificado=1 WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " SET password=:password WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Verificar contraseña
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Listar todos los usuarios (para admin) - CORREGIDO
     */
    public function readAll($page = 1, $limit = 10, $search = '', $showDeleted = false) {
        $offset = ($page - 1) * $limit;

        $deleted_condition = $showDeleted ? "u.deleted_at IS NOT NULL" : "u.deleted_at IS NULL";

        // Query base
        $query = "SELECT u.id, u.nombre, u.apellido, u.razon_social_empresa, u.cuit,
                        u.correo_electronico, u.celular, u.ciudad, u.direccion, u.provincia,
                        u.email_verificado, u.tipo_usuario_id, tu.nombre as tipo_usuario_nombre, u.created_at, u.deleted_at
                FROM " . $this->table_name . " u
                LEFT JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id
                WHERE " . $deleted_condition;

        // Agregar búsqueda si se proporciona
        $where_clause = "";
        if (!empty($search)) {
            // ✅ SOLUCION: Usar placeholders únicos para cada campo
            $where_clause = " AND (u.nombre LIKE :search1 OR u.apellido LIKE :search2
                                OR u.correo_electronico LIKE :search3 OR u.cuit LIKE :search4
                                OR u.razon_social_empresa LIKE :search5)";
        }
        
        // Query para contar total
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " u WHERE " . $deleted_condition . $where_clause;
        
        // Query final con paginación
        $query .= $where_clause . " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";
        
        try {
            // Preparar parámetro de búsqueda
            $search_param = null;
            if (!empty($search)) {
                $search_param = "%{$search}%";
            }
            
            // Obtener total de registros
            $count_stmt = $this->conn->prepare($count_query);
            if (!empty($search)) {
                // ✅ SOLUCION: Bind para cada placeholder único
                $count_stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
                $count_stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
                $count_stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
                $count_stmt->bindValue(':search4', $search_param, PDO::PARAM_STR);
                $count_stmt->bindValue(':search5', $search_param, PDO::PARAM_STR);
            }
            $count_stmt->execute();
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener registros paginados
            $stmt = $this->conn->prepare($query);
            if (!empty($search)) {
                // ✅ SOLUCION: Bind para cada placeholder único
                $stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
                $stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
                $stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
                $stmt->bindValue(':search4', $search_param, PDO::PARAM_STR);
                $stmt->bindValue(':search5', $search_param, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $usuarios = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usuarios[] = $row;
            }
            
            return [
                'data' => $usuarios,
                'total' => $total
            ];
            
        } catch (Exception $e) {
            error_log("Error en readAll: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0
            ];
        }
    }
    
    /**
     * Obtener estadísticas de usuarios (para admin)
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total de usuarios
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['totalUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Usuarios registrados hoy
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                      WHERE DATE(created_at) = CURDATE()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['newUsersToday'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Usuarios con email verificado (activos)
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                      WHERE email_verificado = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['activeUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Usuarios por tipo
            $query = "SELECT tu.nombre as tipo, COUNT(*) as cantidad 
                      FROM " . $this->table_name . " u
                      LEFT JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id
                      GROUP BY u.tipo_usuario_id, tu.nombre";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $userTypes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $userTypes[] = $row;
            }
            $stats['userTypes'] = $userTypes;
            
            // Usuarios registrados en los últimos 7 días
            $query = "SELECT DATE(created_at) as fecha, COUNT(*) as cantidad 
                      FROM " . $this->table_name . " 
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY fecha DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $weeklyStats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $weeklyStats[] = $row;
            }
            $stats['weeklyRegistrations'] = $weeklyStats;
            
            // Para compatibilidad con el frontend, agregar totalProducts
            $stats['totalProducts'] = 0; // Se actualizará cuando implementes productos
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error en getStats: " . $e->getMessage());
            return [
                'totalUsers' => 0,
                'newUsersToday' => 0,
                'activeUsers' => 0,
                'totalProducts' => 0,
                'userTypes' => [],
                'weeklyRegistrations' => []
            ];
        }
    }
    
    /**
     * Eliminar usuario (soft delete)
     */
    public function delete() {
        $query = "UPDATE " . $this->table_name . "
                  SET deleted_at = NOW()
                  WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restore() {
        $query = "UPDATE " . $this->table_name . "
                  SET deleted_at = NULL
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
    
    /**
     * Guardar token de reset de contraseña
     * REQUIERE en la tabla usuarios:
     *   ALTER TABLE usuarios ADD COLUMN password_reset_token VARCHAR(255) NULL DEFAULT NULL;
     *   ALTER TABLE usuarios ADD COLUMN password_reset_expires_at DATETIME NULL DEFAULT NULL;
     */
    public function saveResetToken($email, $token, $expiresAt) {
        $query = "UPDATE " . $this->table_name . "
                  SET password_reset_token=:token, password_reset_expires_at=:expires_at
                  WHERE correo_electronico=:email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expiresAt);
        $stmt->bindParam(":email", $email);
        return $stmt->execute();
    }

    /**
     * Buscar usuario por token de reset (solo si no expiró)
     */
    public function findByResetToken($token) {
        $query = "SELECT id, correo_electronico, nombre FROM " . $this->table_name . "
                  WHERE password_reset_token=:token AND password_reset_expires_at > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Limpiar token de reset después de uso
     */
    public function clearResetToken() {
        $query = "UPDATE " . $this->table_name . "
                  SET password_reset_token=NULL, password_reset_expires_at=NULL
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    /**
     * Validar datos del usuario
     */
    public function validate() {
        $errors = [];
        
        // Validar nombre
        if(empty($this->nombre)) {
            $errors[] = "El nombre es requerido";
        }
        
        // Validar apellido
        if(empty($this->apellido)) {
            $errors[] = "El apellido es requerido";
        }
        
        // Validar razón social
        if(empty($this->razon_social_empresa)) {
            $errors[] = "La razón social es requerida";
        }
        
        // Validar CUIT
        if(empty($this->cuit)) {
            $errors[] = "El CUIT es requerido";
        } elseif(!$this->validateCuit($this->cuit)) {
            $errors[] = "El CUIT no tiene un formato válido";
        }
        
        // Validar email
        if(empty($this->correo_electronico)) {
            $errors[] = "El correo electrónico es requerido";
        } elseif(!filter_var($this->correo_electronico, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El correo electrónico no es válido";
        }
        
        // Validar contraseña
        if(empty($this->password)) {
            $errors[] = "La contraseña es requerida";
        } elseif(strlen($this->password) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        return $errors;
    }
    
    /**
     * Validar formato de CUIT argentino
     */
    private function validateCuit($cuit) {
        // Remover guiones y espacios
        $cuit = preg_replace('/[^0-9]/', '', $cuit);
        
        // Verificar que tenga 11 dígitos
        if(strlen($cuit) != 11) {
            return false;
        }
        
        // Algoritmo de validación de CUIT argentino
        $multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        
        for($i = 0; $i < 10; $i++) {
            $suma += intval($cuit[$i]) * $multiplicadores[$i];
        }
        
        $resto = $suma % 11;
        $digito_verificador = $resto < 2 ? $resto : 11 - $resto;
        
        return intval($cuit[10]) == $digito_verificador;
    }
}
?>