<?php
/*
* Dependencies generated by the foreign keys
*/

/* Primary key found: id*/

require_model('recogida_empresa.php');

class recogida_certificado extends fs_model
{
    var $id;
    var $n_certificado;
    var $fecha;
    var $empresa_id;
    var $direccion_id;
    var $observaciones2;
    var $link;
    var $tipo_id;
    //Variable para recoger el nombre del Cliente o Proveedor
    var $nombre;
    var $direccion;
    //Variable para recoger los años que existen
    var $anos;

    public function __construct($data=FALSE)
    {
        $pluginname = str_replace(realpath(".") . "/", "",  realpath(__DIR__ . "/..") ) . "/";
        parent::__construct('recogida_certificados', $pluginname);
        
        if($data)
        {
            $this->id = $this->intval($data['id']);
            $this->n_certificado = $this->intval($data['n_certificado']);
            $this->fecha = date('d-m-Y', strtotime($data['fecha']));
            $this->empresa_id = $data['empresa_id'];
            $this->direccion_id = $data['direccion_id'];
            $this->observaciones2 = $this->no_html($data['observaciones2']);
            $this->link = $data['link'];
            $this->tipo_id = $this->str2bool($data['tipo_id']);
            $this->nombre = $data['nombre'];
            $this->direccion = $data['direccion'];
            $this->anos = $data['anos'];
        }else{
            $this->id = 0;
            $this->n_certificado = 0;
            $this->fecha = date('d-m-Y');
            $this->empresa_id = NULL;
            $this->direccion_id = NULL;
            $this->observaciones2 = NULL;
            $this->link = NULL;
            $this->tipo_id = 0;
            $this->nombre = NULL;
            $this->direccion = NULL;
            $this->anos = NULL;
        }
    }

    /**
     * Esta función es llamada al crear una tabla.
     * Permite insertar valores en la tabla.
     */
    protected function install()
    {
        return '';
    }

    /**
     * Esta función sirve para eliminar los datos del objeto de la base de datos
     */
    public function delete()
    {
        
        $value = $this->var2str($this->id);
        if($this->id)
        {
            $sql = "DELETE FROM {$this->table_name} WHERE id = $value;";
            return $this->db->exec($sql);
        }
        
    }
    
    /**
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    public function exists()
    {
        
        if($this->id)
        {
            $value = $this->var2str($this->id);
            return $this->db->select("SELECT * FROM {$this->table_name} WHERE id = $value;");
        }
        
        return false;
    }
    /**
     * Esta función devuelve TRUE si los datos estan correctos
     * 
     */
    public function valida()
    {
        $status = FALSE;
        
        if($this->n_certificado == 0)
            $this->new_error_msg("Numero de certificado no indicado");             
        else
            $status = TRUE;
      
      return $status;         
    }
    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    public function save() {

        if ($this->valida()) {
            if ($this->exists()) {
                $sql = "UPDATE {$this->table_name} SET n_certificado = " . $this->var2str($this->n_certificado) . "
                    , fecha = " . $this->var2str($this->fecha) . "
                    , empresa_id = " . $this->var2str($this->empresa_id) . "
                    , direccion_id = " . $this->var2str($this->direccion_id) . "
                    , observaciones2 = " . $this->var2str($this->observaciones2) . "
                    , link = " . $this->var2str($this->link) . "
                    , tipo_id = " . $this->var2str($this->tipo_id) . "
                    WHERE id = $this->var2str($this->id);";
                    
                    return $this->db->exec($sql);
            } else {
                $sql = "INSERT INTO {$this->table_name} (
                                    n_certificado
                                    , fecha
                                    , empresa_id
                                    , direccion_id
                                    , observaciones2
                                    , link
                                    , tipo_id
                                    
                                ) VALUES (
                                       " . $this->var2str($this->n_certificado) . "
                                    ,  " . $this->var2str($this->fecha) . "
                                    ,  " . $this->var2str($this->empresa_id) . "
                                    ,  " . $this->var2str($this->direccion_id) . "
                                    ,  " . $this->var2str($this->observaciones2) . "
                                    ,  " . $this->var2str($this->link) . "
                                    ,  " . $this->var2str($this->tipo_id) . "
                                );";
                
                if ($this->db->exec($sql)) {
                    return TRUE;
                } else
                    return FALSE;                
            }
        } else
            return FALSE;
    }

    public function get($cod)
    {
        $cod = $this->var2str($cod);
        return $this->parse($this->db->select("SELECT * FROM {$this->table_name} WHERE id = $cod;"));
    }
    
    public function get_all_offset($offset=0, $limit=FS_ITEM_LIMIT)
    {
        return $this->parse($this->db->select_limit("SELECT * FROM {$this->table_name} ORDER BY id DESC;", $limit, $offset), true);
    }
    public function get_all()
    {
        return $this->parse($this->db->select("SELECT * FROM {$this->table_name} ORDER BY n_certificado DESC;"), true);
    }
    public function get_all_in($ano='')
    {
        $ano = $this->var2str($ano);
                
        return $this->parse($this->db->select("SELECT {$this->table_name}.*, proveedores.nombre, dirproveedores.direccion FROM "
                . "{$this->table_name} INNER JOIN proveedores ON proveedores.codproveedor = {$this->table_name}.empresa_id "
                . " INNER JOIN dirproveedores ON  dirproveedores.id = {$this->table_name}.direccion_id "
                . "WHERE {$this->table_name}.tipo_id = 1 AND YEAR(fecha) = $ano ORDER BY {$this->table_name}.n_certificado DESC;"), true);
    }
    public function get_all_out($ano='')
    {
        $ano = $this->var2str($ano);
        
        return $this->parse($this->db->select("SELECT {$this->table_name}.*, clientes.nombre, dirclientes.direccion FROM "
                . "{$this->table_name} INNER JOIN clientes ON  clientes.codcliente = {$this->table_name}.empresa_id "
                . " INNER JOIN dirclientes ON dirclientes.id = {$this->table_name}.direccion_id "
                . "WHERE {$this->table_name}.tipo_id = 2 AND YEAR(fecha) = $ano ORDER BY {$this->table_name}.n_certificado DESC;"), true);
        }    
    public function parse($items, $array = false)
    {
        if(count($items) > 1 || $array)
        {
            $list = array();
            foreach($items as $item)
            {
                $list[] = new recogida_certificado($item);
            }
            return $list;
        }
        else if(count($items) == 1)
        {
            return new recogida_certificado($items[0]);
        }
        return null;
    }
    
    public function nextvalue_in($ano=''){
        $ano = $this->var2str($ano);
        
        $data = $this->db->select("SELECT MAX(n_certificado) AS id FROM {$this->table_name} WHERE tipo_id = 1 AND YEAR(fecha)= $ano;");
        
        if ($data)
            return ($data[0]['id'])+1;
        else
            return FALSE;
   }    
    public function nextvalue_out($ano=''){
        $ano = $this->var2str($ano);
        
        $data = $this->db->select("SELECT MAX(n_certificado) AS id FROM {$this->table_name} WHERE tipo_id = 2 AND YEAR(fecha)= $ano;");
        
        if ($data)
            return ($data[0]['id'])+1;
        else
            return FALSE;
   }

    /**
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    public function existe_certificado($ncertificado = 0, $tipo = 0, $ano = 0)
    {
        
        if($ncertificado != 0 AND $tipo != 0)
        {
            $value = $this->var2str($ncertificado);
            $tipo_id = $this->var2str($tipo);
            $ano = $this->var2str($ano);
            return $this->db->select("SELECT id FROM {$this->table_name} WHERE n_certificado = $value AND tipo_id = $tipo_id AND YEAR(fecha) = $ano;");
        }
        
        return false;
    }
    
    public function lineas_certificado($desde='', $hasta='', $tipo=0, $empresa_id='', $direccion_id=''){
        $lineas = new recogida_empresa();
        return $lineas->search_cert('', $desde, $hasta, $tipo, $empresa_id, $direccion_id,'','','fecha');
    }
    
    public function lista_anos($tipo = 0){
        $tipo = $this->var2str($tipo);
        return $this->parse($this->db->select("SELECT DISTINCT YEAR(fecha) AS anos FROM {$this->table_name} WHERE tipo_id = $tipo ORDER BY anos DESC;"),TRUE);
    }
   
}
