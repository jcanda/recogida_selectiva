<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class recogida_diario extends fs_model
{
   public $recogida_id;
   public $fecha;
   public $entidad_id;
   public $material_id;
   public $entrada;
   public $salida;
   public $tipo_id;
   public $matricula;
   public $ayunta_id;
   public $ecovidrio;
   public $notas;
   //Variables para recoger los nombres de la s entidades
   public $entidad_nombre;

   public function __construct($a = FALSE) {
       
        parent::__construct('recogida_diario', 'plugins/recogida_selectiva/');

        if ($a) {
            $this->recogida_id = intval($a['recogida_id']);
            
            $this->fecha = NULL;
            if(isset($a['fecha']) )
                $this->fecha = date('d-m-Y', strtotime($a['fecha'])); 
            
            $this->entidad_id = intval($a['entidad_id']);
            $this->material_id = intval($a['material_id']);
            $this->entrada = floatval($a['entrada']);
            $this->salida = floatval($a['salida']);
            $this->tipo_id = intval($a['tipo_id']);
            $this->matricula = $this->no_html($a['matricula']);
            $this->ayunta_id = intval($a['ayunta_id']);
            $this->ecovidrio = $this->str2bool($a['ecovidrio']);
            $this->notas = $this->no_html($a['notas']);
            
            $this->entidad_nombre = $this->no_html($a['entidad_nombre']);
        }else{
            $this->recogida_id = 0;
            $this->fecha = date('d-m-Y');
            $this->entidad_id = 0;
            $this->material_id = 0;
            $this->entrada = 0;
            $this->salida = 0;
            $this->tipo_id = 0;
            $this->matricula = ''; 
            $this->ayunta_id = 0;
            $this->ecovidrio = 0;
            $this->notas = '';
            
            $this->entidad_nombre = '';
        }
    }

    public function install()
   {
      return '';
   }  

   public function delete()
   {
       return $this->db->exec("DELETE FROM recogida_diario WHERE recogida_id = ".$this->var2str($this->recogida_id).";");
   }

   public function save() {      
        if ($this->valida()) {            
            if ($this->exists()) {
                
                $sql = "UPDATE recogida_diario SET fecha = " . $this->var2str($this->fecha) . ",
               entidad_id = " . $this->var2str($this->entidad_id) . ", material_id = " . $this->var2str($this->material_id) . ",
               entrada = " . $this->var2str($this->entrada) . ", salida = " . $this->var2str($this->salida) . ",
               tipo_id = " . $this->var2str($this->tipo_id) . ", matricula = " . $this->var2str($this->matricula) . ",
               ayunta_id = " . $this->var2str($this->ayunta_id) . ", ecovidrio = " . $this->var2str($this->ecovidrio) . ",    
               notas = " . $this->var2str($this->notas) . " WHERE recogida_id = " . $this->var2str($this->recogida_id) . ";";

                return $this->db->exec($sql);
                
            } else {
                $sql = "INSERT INTO recogida_diario (`fecha`, `entidad_id`, `material_id`, `entrada`, `salida`, `tipo_id`, `matricula`, `ayunta_id`, `ecovidrio`, `notas`) 
               VALUES (" . $this->var2str($this->fecha) . "," . $this->var2str($this->entidad_id) . ",
               " . $this->var2str($this->material_id) . "," . $this->var2str($this->entrada) . ",
               " . $this->var2str($this->salida) . "," . $this->var2str($this->tipo_id) . ",
               " . $this->var2str($this->matricula) . "," . $this->var2str($this->ayunta_id) . "," . $this->var2str($this->ecovidrio) . ",   
               " . $this->var2str($this->notas) . ");";

                if ($this->db->exec($sql)) {
                    $this->recogida_id = $this->db->lastval();
                    return TRUE;
                } else
                    return FALSE;
            }
        } else
            return FALSE;
   } 

    public function exists() {
        if (is_null($this->recogida_id)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM recogida_diario WHERE recogida_id = " . $this->var2str($this->recogida_id) . ";");
        }
    }

    public function valida()
   {
        $status = FALSE;
        $this->matricula = $this->no_html($this->matricula);
        $this->notas = $this->no_html($this->notas);
        
        $this->matricula = strtoupper($this->matricula); 
        $this->matricula = preg_replace('[\s+]', '', $this->matricula);
        $this->matricula = preg_replace('[\-]', '', $this->matricula);
        /// valido las variables, cambio MAY/MIN y simplemente eliminar el html de las variables
        if( $this->material_id == 0 )
            $this->new_error_msg("Material no indicado.");      
        else if ($this->entrada == 0 AND $this->salida == 0)
            $this->new_error_msg("No ha introducido ninguna cantidad de Entrada ni de Salida."); 
        else if ($this->entrada != 0 AND $this->salida != 0)
            $this->new_error_msg("Ha introducido cantidades en Entrada y Salida.");         
        else
            $status = TRUE;
      
      return $status;    
   }   
   public function all($offset=0, $limit=FS_ITEM_LIMIT) {
        $recogidas = array();
        
        $sql = "SELECT {$this->table_name}.*, recogida_entidad.entidad_nombre"
        . " FROM {$this->table_name} INNER JOIN recogida_entidad ON recogida_entidad.entidad_id = {$this->table_name}.entidad_id"
        . " WHERE 1 ORDER BY fecha DESC";
        
        $data = $this->db->select_limit($sql, $limit, $offset);
        
        if ($data) {
            foreach ($data as $d)
                $recogidas[] = new recogida_diario($d);
        }

        return $recogidas;       
   }  
   
   public function url()
   {
       if( is_null($this->recogida_id) )
      {
         return 'index.php?page=recogidas_diario';
      }
      else
      {
         return 'index.php?page=recogidas_diario&id='.$this->recogida_id;
      }
   }
   
    public function get($id)
   {
      $sql = "SELECT * FROM `recogida_diario` WHERE recogida_id = " . $this->var2str($id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return new recogida_diario($data[0]);
      else
         return FALSE;       
   }
   
   public function nombre_empresa() {
      $sql = "SELECT entidad_nombre FROM `recogida_entidad` WHERE entidad_id = " . $this->var2str($this->entidad_id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return $data[0]['entidad_nombre'];
      else
         return FALSE;        
   }
   
   public function nombre_ayunta() {
      $sql = "SELECT entidad_nombre FROM `recogida_entidad` WHERE entidad_id = " . $this->var2str($this->ayunta_id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return $data[0]['entidad_nombre'];
      else
         return FALSE;         
   }


   public function tipos()
   {
      $tipos = array(
          0 => '-',
          1 => 'Iglú',
          2 => 'Puerta a Puerta'    
      );
      
      return $tipos;
   }
   
   public function nombre_tipo()
   {
      $tipos = $this->tipos();
      return $tipos[$this->tipo_id];
   }   

   public function materiales()
   {
      $materiales = array(
          1 => 'Cartón',
          2 => 'Chapa',
          3 => 'Vidrio'
      );
      
      return $materiales;
   }
   public function nombre_material()
   {
      $materiales = $this->materiales();
      return $materiales[$this->material_id];
   }   
   public function search($buscar='', $desde='', $hasta='', $material='todos',$orden="fecha")
   {
      $entidadlist = array();
      
      $sql = "SELECT {$this->table_name}.*, recogida_entidad.entidad_nombre
         FROM {$this->table_name} INNER JOIN recogida_entidad ON recogida_entidad.entidad_id = {$this->table_name}.entidad_id
         WHERE recogida_id > 0";
      
      if($buscar != '')
      {
         $sql .= " AND ((upper(matricula) LIKE upper('%".$buscar."%')) OR (notas LIKE '%".$buscar."%')
            OR (upper(entidad_nombre) LIKE upper('%".$buscar."%')))";
      }
      
      if($desde != '')
      {
         $sql .= " AND `fecha` >= ".$this->var2str($desde);
      }
      
      if($hasta != '')
      {
         $sql .= " AND `fecha` <= ".$this->var2str($hasta);
      }       
      
      if($material != "todos" AND $material != "1")
      {
         $sql .= " AND material_id = ".$material;
      }
      else 
      {
          if($material == "1")
          {
              $sql .= " AND material_id = ".$material;
          }
          //si no entra en ninguno de los 2 if anteriores muestra todos las entidades.
      }
      
      
      
      $sql.= " ORDER BY ".$orden." ASC ";
      
      $data = $this->db->select($sql.";");
      if($data)
      {
         foreach($data as $d)
            $entidadlist[] = new recogida_diario($d);
      }
      
      return $entidadlist;       
   }   
}