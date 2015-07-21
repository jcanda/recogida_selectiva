<?php
/*
* Generado por Jcanda
*/


class recogida_doc extends fs_model
{
    var $id;
    var $autorizacion;
    var $ler;
    var $descripcion;
    var $tipo_material;


    public function __construct($data=FALSE)
    {
        $pluginname = str_replace(realpath(".") . "/", "",  realpath(__DIR__ . "/..") ) . "/";
        parent::__construct('recogida_docs', $pluginname);
        
        if($data)
        {
            $this->id = $this->intval($data['id']);
            $this->autorizacion = $data['autorizacion'];
            $this->ler = $data['ler'];
            $this->descripcion = $data['descripcion'];
            $this->tipo_material = $data['tipo_material'];
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
                        , autorizacion = " . $this->var2str($this->autorizacion) . "
                        , ler = " . $this->var2str($this->ler) . "
                        , descripcion = " . $this->var2str($this->descripcion) . "
                        , tipo_material = " . $this->var2str($this->tipo_material) . "
                          WHERE id = $value";
                return $this->db->exec($sql);
            }
            
        }
        else
        {
            $sql = "INSERT INTO {$this->table_name} (
                                    id
                                    , autorizacion
                                    , ler
                                    , descripcion
                                    , tipo_material
                                    
                                ) VALUES (
                                     " . $this->var2str($this->id) . "
                                    ,  " . $this->var2str($this->autorizacion) . "
                                    ,  " . $this->var2str($this->ler) . "
                                    ,  " . $this->var2str($this->descripcion) . "
                                    ,  " . $this->var2str($this->tipo_material) . "
                                    
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
        
        $value = $this->var2str($this->id);
        if($this->id)
        {
            $sql = "DELETE FROM {$this->table_name} WHERE id = $value)";
            return $this->db->exec($sql);
        }
        
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
    public function get_all($unicos = FALSE)
    {
        if($unicos)
            return $this->parse($this->db->select("SELECT id, tipo_material, autorizacion FROM recogida_docs GROUP BY tipo_material ORDER BY id ASC"), true);
        else
            return $this->parse($this->db->select("SELECT * FROM {$this->table_name} ORDER BY id DESC"), true);
    }
    public function parse($items, $array = false)
    {
        if(count($items) > 1 || $array)
        {
            $list = array();
            foreach($items as $item)
            {
                $list[] = new recogida_doc($item);
            }
            return $list;
        }
        else if(count($items) == 1)
        {
            return new recogida_doc($items[0]);
        }
        return null;
    }
    
    public function search($buscar = '', $tipo = '', $orden = "id") {
        $entidadlist = array();

        $sql = "SELECT *
                FROM {$this->table_name}
                WHERE id > 0";
        
        
        //Primero compruebo si hay texto a buscar
        if ($buscar != '') {
            $sql .= " AND `autorizacion` = " . $this->var2str($buscar);
        }

        //Segundo compruebo el parametro tipo para filtrar
        if ($tipo != '') {
            //Si el parametro es 
            $sql .= " AND `tipo_material` = " . $this->var2str($tipo);
        }

        //Finalmente compruebo el orden
        $sql.= " ORDER BY " . $orden . " DESC ";

        $data = $this->db->select($sql . ";");
        if ($data) {
            foreach ($data as $d)
                $entidadlist[] = new recogida_doc ($d);
        }

        return $entidadlist;
    }    

}
