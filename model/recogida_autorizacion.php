<?php

/* Primary key found: id_aut*/
class recogida_autorizacion extends fs_model
{
    var $id_aut;
    var $autorizacion;
    var $concepto_aut;
    var $cod_operacion;
    var $almacen_aut;


    public function __construct($data=FALSE)
    {
        $pluginname = str_replace(realpath(".") . "/", "",  realpath(__DIR__ . "/..") ) . "/";
        parent::__construct('recogida_autorizacion', $pluginname);
        
        if($data)
        {
            $this->id_aut = $this->intval($data['id_aut']);
            $this->autorizacion = $data['autorizacion'];
            $this->concepto_aut = $data['concepto_aut'];
            $this->cod_operacion = $data['cod_operacion'];
            $this->almacen_aut = $this->str2bool($data['almacen_aut']);
        }else{
            $this->id_aut = 0;
            $this->autorizacion = NULL;
            $this->concepto_aut = NULL;
            $this->cod_operacion = NULL;
            $this->almacen_aut = 0;            
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
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    public function exists()
    {
        
        if($this->id_aut)
        {
            $value = $this->var2str($this->id_aut);
            return $this->db->select("SELECT * FROM {$this->table_name} WHERE id_aut = $value");
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
            $value = $this->var2str($this->id_aut);
            if($this->id_aut)
            {
                $sql = "UPDATE {$this->table_name} SET id_aut = " . $this->var2str($this->id_aut) . "
                        , autorizacion = " . $this->var2str($this->autorizacion) . "
                        , concepto_aut = " . $this->var2str($this->concepto_aut) . "
                        , cod_operacion = " . $this->var2str($this->cod_operacion) . "
                        , almacen_aut = " . $this->var2str($this->almacen_aut) . "
                          WHERE id_aut = $value";
                return $this->db->exec($sql);
            }
            
        }
        else
        {
            $sql = "INSERT INTO {$this->table_name} (
                                    id_aut
                                    , autorizacion
                                    , concepto_aut
                                    , cod_operacion
                                    , almacen_aut
                                    
                                ) VALUES (
                                     " . $this->var2str($this->id_aut) . "
                                    ,  " . $this->var2str($this->autorizacion) . "
                                    ,  " . $this->var2str($this->concepto_aut) . "
                                    ,  " . $this->var2str($this->cod_operacion) . "
                                    ,  " . $this->var2str($this->almacen_aut) . "
                                    
                                )";
            return $this->db->exec($sql);
        }

        return false;
    }

    /**
     * Esta función sirve para eliminar los datos del objeto de la base de datos
     */
    public function delete()
    {
        
        $value = $this->var2str($this->id_aut);
        if($this->id_aut)
        {
            $sql = "DELETE FROM {$this->table_name} WHERE id_aut = $value)";
            return $this->db->exec($sql);
        }
        
    }
    
    public function get($cod)
    {
        $cod = $this->var2str($cod);
        return $this->parse($this->db->select("SELECT * FROM {$this->table_name} WHERE id_aut = $cod"));
    }
    
    public function get_all_offset($offset=0, $limit=FS_ITEM_LIMIT)
    {
        return $this->parse($this->db->select_limit("SELECT * FROM {$this->table_name} ORDER BY id_aut DESC", $limit, $offset), true);
    }
    public function get_all()
    {
        return $this->parse($this->db->select("SELECT * FROM {$this->table_name} ORDER BY id_aut DESC"), true);
    }
    public function parse($items, $array = false)
    {
        if(count($items) > 1 || $array)
        {
            $list = array();
            foreach($items as $item)
            {
                $list[] = new recogida_autorizacion($item);
            }
            return $list;
        }
        else if(count($items) == 1)
        {
            return new recogida_autorizacion($items[0]);
        }
        return null;
    }

}
