<?php
/*
* Dependencies generated by the foreign keys
*/

/* Primary key found: id*/
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
    var $nombre;

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
            $sql = "DELETE FROM {$this->table_name} WHERE id = $value";
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
            return $this->db->select("SELECT * FROM {$this->table_name} WHERE id = $value");
        }
        
        return false;
    }

    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    public function save()
    {
        $sql = "";
        if($this->exists())
        {
            $value = $this->var2str($this->id);
            if($this->id)
            {
                $sql = "UPDATE {$this->table_name} SET id = " . $this->var2str($this->id) . "
                        , n_certificado = " . $this->var2str($this->n_certificado) . "
                        , fecha = " . $this->var2str($this->fecha) . "
                        , empresa_id = " . $this->var2str($this->empresa_id) . "
                        , direccion_id = " . $this->var2str($this->direccion_id) . "
                        , observaciones2 = " . $this->var2str($this->observaciones2) . "
                        , link = " . $this->var2str($this->link) . "
                        , tipo_id = " . $this->var2str($this->tipo_id) . "
                          WHERE id = $value";
                return $this->db->exec($sql);
            }
            
        }
        else
        {
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
                                )";
            return $this->db->exec($sql);
        }

        return false;
    }
    
    public function get($cod)
    {
        $cod = $this->var2str($cod);
        return $this->parse($this->db->select("SELECT * FROM {$this->table_name} WHERE id = $cod"));
    }
    
    public function get_all_offset($offset=0, $limit=FS_ITEM_LIMIT)
    {
        return $this->parse($this->db->select_limit("SELECT * FROM {$this->table_name} ORDER BY id DESC", $limit, $offset), true);
    }
    public function get_all()
    {
        return $this->parse($this->db->select("SELECT * FROM {$this->table_name} ORDER BY n_certificado DESC"), true);
    }
    public function get_all_in()
    {
        return $this->parse($this->db->select("SELECT * FROM "
                . "{$this->table_name} INNER JOIN proveedores ON {$this->table_name}.empresa_id = proveedores.codproveedor "
                . "WHERE {$this->table_name}.tipo_id = 1 ORDER BY {$this->table_name}.n_certificado DESC"), true);
    }
    public function get_all_out()
    {
        return $this->parse($this->db->select("SELECT * FROM "
                . "{$this->table_name} INNER JOIN clientes ON {$this->table_name}.empresa_id = clientes.codcliente "
                . "WHERE {$this->table_name}.tipo_id = 2 ORDER BY {$this->table_name}.n_certificado DESC"), true);
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
    
    public function nextvalue_in(){

        $data = $this->db->select("SELECT MAX(n_certificado) AS id FROM {$this->table_name} WHERE tipo_id = 1");
        
        if ($data)
            return ($data[0]['id'])+1;
        else
            return FALSE;
   }    
    public function nextvalue_out(){

        $data = $this->db->select("SELECT MAX(n_certificado) AS id FROM {$this->table_name} WHERE tipo_id = 2");
        
        if ($data)
            return ($data[0]['id'])+1;
        else
            return FALSE;
   }
}