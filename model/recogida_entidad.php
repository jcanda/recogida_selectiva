<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class recogida_entidad extends fs_model
{
   public $entidad_id;
   public $entidad_nombre;
   public $entidad_cifnif;
   public $entidad_codpostal;
   public $entidad_telefono;
   public $entidad_tipo;

   public function __construct($a = FALSE) {
       
        parent::__construct('recogida_entidad', 'plugins/recogida_selectiva/');

        if ($a) {
            $this->entidad_id = intval($a['entidad_id']);
            $this->entidad_nombre = $a['entidad_nombre'];
            $this->entidad_cifnif = $a['entidad_cifnif'];
            $this->entidad_codpostal = $a['entidad_codpostal'];
            $this->entidad_telefono = $a['entidad_telefono'];
            $this->entidad_tipo = intval($a['entidad_tipo']);
        }else{
            $this->entidad_id = 0;
            $this->entidad_nombre = '';
            $this->entidad_cifnif = '';
            $this->entidad_codpostal = '';
            $this->entidad_telefono = '';
            $this->entidad_tipo = 0;            
        }
    }

    public function install()
   {
      return '';
   }    

   public function delete()
   {
       return $this->db->exec("DELETE FROM recogida_entidad WHERE entidad_id = ".$this->var2str($this->entidad_id).";");
   }

   public function save() {
       
        if ($this->valida()) {            
            if ($this->exists()) {
                
                $sql = "UPDATE recogida_entidad SET entidad_nombre = " . $this->var2str($this->entidad_nombre) . ",
               entidad_telefono = " . $this->var2str($this->entidad_telefono) . ", entidad_tipo = " . $this->var2str($this->entidad_tipo) . ",
               entidad_cifnif = " . $this->var2str($this->entidad_cifnif) . ",
               entidad_codpostal = " . $this->var2str($this->entidad_codpostal) . " WHERE entidad_id = " . $this->var2str($this->entidad_id) . ";";

                return $this->db->exec($sql);
                
            } else {
                $sql = "INSERT INTO recogida_entidad (entidad_nombre, entidad_telefono, entidad_tipo, entidad_cifnif, entidad_codpostal) 
               VALUES (" . $this->var2str($this->entidad_nombre) . "," . $this->var2str($this->entidad_telefono) . ",
               " . $this->var2str($this->entidad_tipo) . "," . $this->var2str($this->entidad_cifnif) . ",
               " . $this->var2str($this->entidad_codpostal) . ");";

                if ($this->db->exec($sql)) {
                    $this->entidad_id = $this->db->lastval();
                    return TRUE;
                } else
                    return FALSE;
            }
        } else
            return FALSE;
    }

    public function exists() {
        if (is_null($this->entidad_id)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM recogida_entidad WHERE entidad_id = " . $this->var2str($this->entidad_id) . ";");
        }
    }

    public function valida()
   {
        $this->entidad_nombre = $this->no_html($this->entidad_nombre);
        $this->entidad_cifnif = $this->no_html($this->entidad_cifnif);
        $this->entidad_codpostal = $this->no_html($this->entidad_codpostal);
        $this->entidad_telefono = $this->no_html($this->entidad_telefono);
        
        if ($this->entidad_tipo==2)
            $this->entidad_nombre = strtoupper($this->entidad_nombre);
        else
            $this->entidad_nombre = ucwords($this->entidad_nombre);
        
        $this->entidad_cifnif = strtoupper($this->entidad_cifnif);
        $this->entidad_codpostal = strtoupper($this->entidad_codpostal);
        
        /// valido las variables, cambio MAY/MIN y simplemente eliminar el html de las variables
        return TRUE;
   }   
   public function all() {
        $entidades = array();
        
        $sql = "SELECT * FROM recogida_entidad;";
        
        $data = $this->db->select($sql);
        
        if ($data) {
            foreach ($data as $d)
                $entidades[] = new recogida_entidad($d);
        }

        return $entidades;
    }

   public function url()
   {
       if( is_null($this->entidad_id) )
      {
         return 'index.php?page=recogida_entidades';
      }
      else
      {
         return 'index.php?page=recogida_entidades&id='.$this->entidad_id;
      }
   }
   
    public function get($id)
   {
      $sql = "SELECT * FROM `recogida_entidad` WHERE entidad_id = " . $this->var2str($id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return new recogida_entidad($data[0]);
      else
         return FALSE;       
   }   

   public function tipos()
   {
      $tipos = array(
          1 => 'Ayuntamiento',
          2 => 'Empresa'    
      );
      
      return $tipos;
   }
   
   public function nombre_tipo()
   {
      $tipos = $this->tipos();
      return $tipos[$this->entidad_tipo];
   }
   
   public function search($buscar='', $tipo='todos',$orden="entidad_nombre")
   {
      $entidadlist = array();
      
      $sql = "SELECT *
         FROM `recogida_entidad`
         WHERE entidad_id > 0";
      
      if($buscar != '')
      {
         $sql .= " AND ((lower(entidad_nombre) LIKE lower('%".$buscar."%')) OR (entidad_cifnif LIKE '%".$buscar."%')
            OR (lower(entidad_telefono) LIKE lower('%".$buscar."%')))";
      }
      
      if($tipo != "todos" AND $tipo != "1")
      {
         $sql .= " AND entidad_tipo = ".$tipo;
      }
      else 
      {
          if($tipo == "1")
          {
              $sql .= " AND entidad_tipo = ".$tipo;
          }
          //si no entra en ninguno de los 2 if anteriores muestra todos las entidades.
      }
      $sql.= " ORDER BY ".$orden." ASC ";
      
      $data = $this->db->select($sql.";");
      if($data)
      {
         foreach($data as $d)
            $entidadlist[] = new recogida_entidad($d);
      }
      
      return $entidadlist;       
   }   
}
