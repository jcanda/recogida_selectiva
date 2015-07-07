<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class recogida_empresa extends fs_model
{
   public $recogida_id;
   public $fecha;
   public $empresa_id;
   public $articulo_id;
   public $ler_ambiente;
   public $descrip_ambiente;
   public $entrada_empresa;
   public $salida_empresa;
   public $tipo_id;
   public $matricula;
   public $notas;    

   public function __construct($a = FALSE) {
       
        parent::__construct('recogida_empresa', 'plugins/recogida_selectiva/');

        if ($a) {
            $this->recogida_id = intval($a['recogida_id']);
            
            $this->fecha = NULL;
            if(isset($a['fecha']) )
                $this->fecha = date('d-m-Y', strtotime($a['fecha'])); 
            
            $this->empresa_id = $a['empresa_id'];
            $this->articulo_id = $a['articulo_id'];
            $this->ler_ambiente = $a['ler_ambiente'];
            $this->descrip_ambiente = $this->no_html($a['descrip_ambiente']);
            $this->entrada_empresa = floatval($a['entrada_empresa']);
            $this->salida_empresa = floatval($a['salida_empresa']);
            $this->tipo_id = intval($a['tipo_id']);
            $this->matricula = $this->no_html($a['matricula']);
            $this->notas = $this->no_html($a['notas']);
        }else{
            $this->recogida_id = 0;
            $this->fecha = date('d-m-Y');
            $this->empresa_id = NULL;
            $this->articulo_id = NULL;
            $this->ler_ambiente = NULL;
            $this->descrip_ambiente = NULL;
            $this->entrada_empresa = 0;
            $this->salida_empresa = 0;
            $this->tipo_id = 0;
            $this->matricula = ''; 
            $this->notas = '';
        }
    }

    public function install()
   {
      return '';
   }  

   public function delete()
   {
       return $this->db->exec("DELETE FROM recogida_empresa WHERE recogida_id = ".$this->var2str($this->recogida_id).";");
   }

   public function save() {      
        if ($this->valida()) {            
            if ($this->exists()) {
                
                $sql = "UPDATE recogida_empresa SET fecha = " . $this->var2str($this->fecha) . ",
               empresa_id = " . $this->var2str($this->empresa_id) . ", articulo_id = " . $this->var2str($this->articulo_id) . ",
               ler_ambiente = " . $this->var2str($this->ler_ambiente) .",  descrip_ambiente = " . $this->var2str($this->descrip_ambiente) . ", 
               entrada_empresa = " . $this->var2str($this->entrada_empresa) . ", salida_empresa = " . $this->var2str($this->salida_empresa) . ",
               tipo_id = " . $this->var2str($this->tipo_id) . ", matricula = " . $this->var2str($this->matricula) . ",    
               notas = " . $this->var2str($this->notas) . " WHERE recogida_id = " . $this->var2str($this->recogida_id) . ";";

                return $this->db->exec($sql);
                
            } else {
                $sql = "INSERT INTO recogida_empresa (`fecha`, `empresa_id`, `articulo_id`,`ler_ambiente`,`descrip_ambiente`, `entrada_empresa`, `salida_empresa`, `tipo_id`, `matricula`, `notas`) 
               VALUES (" . $this->var2str($this->fecha) . "," . $this->var2str($this->empresa_id) . ",
               " . $this->var2str($this->articulo_id) . "," . $this->var2str($this->ler_ambiente) . ",
               " . $this->var2str($this->descrip_ambiente) . "," . $this->var2str($this->entrada_empresa) . ",
               " . $this->var2str($this->salida_empresa) . "," . $this->var2str($this->tipo_id) . ",
               " . $this->var2str($this->matricula) . "," . $this->var2str($this->notas) . ");";

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
            return $this->db->select("SELECT * FROM recogida_empresa WHERE recogida_id = " . $this->var2str($this->recogida_id) . ";");
        }
    }

    public function valida()
   {
        $status = FALSE;
        
        $this->matricula = strtoupper($this->matricula); 
        $this->matricula = preg_replace('[\s+]', '', $this->matricula);
        $this->matricula = preg_replace('[\-]', '', $this->matricula);
        /// valido las variables, cambio MAY/MIN y simplemente eliminar el html de las variables
        if(is_null($this->empresa_id) )
            $this->new_error_msg("Empresa no indicada...");      
        else if (is_null($this->empresa_id))
            $this->new_error_msg("Empresa no indicada"); 
        else if ($this->entrada_empresa == 0 AND $this->salida_empresa == 0)
            $this->new_error_msg("No ha introducido ninguna cantidad de Entrada ni de Salida."); 
        else if ($this->entrada_empresa != 0 AND $this->salida_empresa != 0)
            $this->new_error_msg("Ha introducido cantidades en Entrada y Salida.");         
        else
            $status = TRUE;
      
      return $status;    
   }   
   public function all($offset=0, $limit=FS_ITEM_LIMIT) {
        $recogidas = array();
        
        $sql = "SELECT * FROM recogida_empresa WHERE 1 ORDER BY recogida_id DESC";
        
        $data = $this->db->select_limit($sql, $limit, $offset);
        
        if ($data) {
            foreach ($data as $d)
                $recogidas[] = new recogida_empresa($d);
        }

        return $recogidas;       
   }  
   
   public function url()
   {
       if( is_null($this->recogida_id) )
      {
         return 'index.php?page=recogidas_empresas';
      }
      else
      {
         return 'index.php?page=recogidas_empresas&id='.$this->recogida_id;
      }
   }
   
    public function get($id)
   {
      $sql = "SELECT * FROM `recogida_empresa` WHERE recogida_id = " . $this->var2str($id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return new recogida_empresa($data[0]);
      else
         return FALSE;       
   }
   
   public function nombre_articulo() {
      $sql = "SELECT descripcion FROM `articulos` WHERE referencia = " . $this->var2str($this->articulo_id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return $data[0]['descripcion'];
      else
         return FALSE;          
   }

   public function nombre_proveedor() {
      $sql = "SELECT nombrecomercial FROM `proveedores` WHERE codproveedor = " . $this->var2str($this->empresa_id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return $data[0]['nombrecomercial'];
      else
         return FALSE;          
   }

   public function nombre_cliente() {
      $sql = "SELECT nombrecomercial FROM `clientes` WHERE codcliente = " . $this->var2str($this->empresa_id) . ";";
        
      $data = $this->db->select($sql);
      
      if($data)
         return $data[0]['nombrecomercial'];
      else
         return FALSE;          
   }   
   
   public function search($buscar='', $desde='', $hasta='', $tipo='todos',$empresa_id='',$orden="fecha")
   {
      $entidadlist = array();
      
      $sql = "SELECT *
         FROM `recogida_empresa`
         WHERE recogida_id > 0";
      
      //Primero compruebo si hay texto a buscar
      if($buscar != '')
      {
         $sql .= " AND ((upper(matricula) LIKE upper('%".$buscar."%')) OR (notas LIKE '%".$buscar."%')
            OR (lower(descrip_ambiente) LIKE lower('%".$buscar."%')) OR (ler_ambiente LIKE '%".$buscar."%'))";
      }
      
      if($desde != '')
      {
         $sql .= " AND `fecha` >= ".$this->var2str($desde);
      }
      
      if($hasta != '')
      {
         $sql .= " AND `fecha` <= ".$this->var2str($hasta);
      }       
      
      //Segundo compruebo el parametro tipo para filtrar
      if($tipo == "2")
      {
          //Si el parametro es 
          $sql .= " AND `tipo_id` = ".$this->var2str($tipo);
      }
      else 
      {
          if($tipo == "1")
          {
              $sql .= " AND `tipo_id` = ".$this->var2str($tipo);
          }
          //si no entra en ninguno de los 2 if anteriores muestra todos entrada y salida.
      }
      
      //Tercero miro si se especifica para una empresa concreto
      if($empresa_id != ''){
         $sql .= " AND `empresa_id` = ".$this->var2str($empresa_id); 
      }
      //Finalmente compruebo el orden
      $sql.= " ORDER BY ".$orden." DESC ";
      
      $data = $this->db->select($sql.";");
      if($data)
      {
         foreach($data as $d)
            $entidadlist[] = new recogida_empresa($d);
      }
      
      return $entidadlist;       
   }   
}
