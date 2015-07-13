<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Recogida Selectiva General
 *
 * @author Zapasoft
 */
require_model('recogida_diario.php');
require_model('recogida_entidad.php');

class recogidas_diario extends fs_controller
{
   public $recogidas_model;
   public $resultado;
   public $busqueda;
   public $offset;
   public $allow_delete;


   public function __construct() {
      parent::__construct(__CLASS__, 'Recogidas Ayuntamiento', 'recogida selectiva', FALSE, TRUE);
      /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
   }

   /**
    * esta función se ejecuta si el usuario ha hecho login,
    * a efectos prácticos, este es el constructor
    */
   protected function process()
   {
      /// desactivamos la barra de botones
      //$this->show_fs_toolbar = FALSE;
      $this->agente = FALSE;
      $this->busqueda = array(
          'contenido' => '',
          'material' => '',
          'desde' => '',
          'hasta' => '',          
          'orden' => 'fecha'
      );
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);      
      
      //cargamos nuestro modelo vacio de tabla recogidas_diario
      $this->recogidas_model = new recogida_diario();
 
        //Para editar una entidad
        if (isset($_GET['id'])) {
            $this->template = "recogida_edita";
            $this->page->title = "Edita ENTIDAD: " . $_GET['id'];
            $this->edita_recogida();
        }
        //PAra añadir Recogida nueva
        else if (isset($_GET['opcion'])) {
            if ($_GET['opcion'] == "nuevarecogida") {
                $this->template = "recogida_nueva";
                $this->page->title = "Nueva Recogida";
                //Esto va si estamos dentro del formulario nueva recogida
                if(isset($_POST['entidad_id'])){
                    $this->nueva_recogida();
                    $this->resultado = $this->recogidas_model->all();
                    $this->template = "recogidas_diario";    
                }
            }
        }
        //Para eliminar 
        else if (isset($_GET['delete'])) {
            $entidad = $this->recogidas_model->get($_GET['delete']);
            if ($entidad) {
                if ($entidad->delete()) {
                    $this->new_message('Entidad eliminada correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar la entidad.');
            } else
                $this->new_error_msg('Entidad no encontrada.');

            $this->template = "recogidas_diario";
            $this->resultado = $this->recogidas_model->all();
        }
        //Para BUSCAR entidad O FILTRAR
        else if (isset($_POST['filtro_tipo']) || isset($_POST['buscar'])){
            $this->busqueda['contenido'] = $_POST['buscar'];
            $this->busqueda['material'] = $_POST['filtro_material'];
            $this->busqueda['desde'] = $_POST['desde'];
            $this->busqueda['hasta'] = $_POST['hasta'];
            
            $this->resultado = $this->recogidas_model->search($this->busqueda['contenido'], $this->busqueda['desde'], $this->busqueda['hasta'], $this->busqueda['material']);
        }
        // sino enseña listado con todas 
        else {
            $this->offset = 0;
            if (isset($_GET['offset']))
                $this->offset = intval($_GET['offset']);
            
            $this->resultado = $this->recogidas_model->all($this->offset);
            $this->template = "recogidas_diario";
        }
    }
   
   protected function edita_recogida() {
        //----------------------------------------------
        // edita una entidad 
        //----------------------------------------------
        $this->resultado = $this->recogidas_model->get($_GET['id']);

        if ($this->resultado) {
            $this->agente = $this->user->get_agente();
        }
        
        if ($this->resultado AND !empty($_POST['entidad_id'])) {
            
            $this->resultado->entidad_id = $_POST['entidad_id'];
            
            if ($_POST['fecha'] != '')
                $this->resultado->fecha = $_POST['fecha'];
            
            $this->resultado->material_id = $_POST['material_id'];
            $this->resultado->tipo_id = $_POST['tipo_id'];
            $this->resultado->entrada = floatval($_POST['entrada']);
            $this->resultado->salida = floatval($_POST['salida']);
            $this->resultado->ayunta_id = $_POST['ayunta_id'];
            
            $this->resultado->ecovidrio = isset($_POST['ecovidrio']);
            
            $this->resultado->matricula = $_POST['matricula'];
            $this->resultado->notas = $_POST['notas'];
        
            if ($this->resultado->save()) 
                $this->new_message('Datos Recogida actualizados correctamente.');
            else 
                $this->new_error_msg('Imposible actualizar los datos de la Recogida.');            
            
        } elseif (!$this->resultado) {
            $this->new_error_msg('Datos recogida no encontrados.');
        }         
   }
   
   protected function nueva_recogida() {
        //----------------------------------------------
        // agrega una recogida nueva
        //----------------------------------------------
        if(!empty($_POST['entidad_id'])){
            
            $this->recogidas_model->entidad_id = $_POST['entidad_id'];
            if ($_POST['fecha'] == '') {
                $this->recogidas_model->fecha = date('d-m-Y');
            } else
                $this->recogidas_model->fecha = $_POST['fecha'];
            $this->recogidas_model->material_id = $_POST['material_id'];
            $this->recogidas_model->tipo_id = $_POST['tipo_id'];
            $this->recogidas_model->entrada = floatval($_POST['entrada']);
            $this->recogidas_model->salida = floatval($_POST['salida']);
            $this->recogidas_model->ayunta_id = $_POST['ayunta_id'];
            $this->recogidas_model->notas = $_POST['notas'];
            $this->recogidas_model->ecovidrio = isset($_POST['ecovidrio']);
            $this->recogidas_model->matricula = $_POST['matricula'];
            
            if ($this->recogidas_model->save()) {
                $this->new_message('Datos de la Recogida guardados correctamente.');
            } else {
                $this->new_error_msg('Imposible guardar los datos de la nueva Recogida.');
                return FALSE;
            }            
        }
        else{
             $this->new_error_msg('Recogida NO creada: Empresa no especificada.');
            return FALSE;
        }        
   }

   public function listar_materiales() {
        $materiales = array();

        /**
         * En recogidas_model::materiales() nos devuelve un array con todos los materiales,
         * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
         */
        foreach ($this->recogidas_model->materiales() as $i => $value)
            $materiales[] = array('material_id' => $i, 'nombre_material' => $value);

        return $materiales;
    }
 
   public function listar_tipos() {
        $tipos = array();

        /**
         * En recogidas_model::materiales() nos devuelve un array con todos los materiales,
         * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
         */
        foreach ($this->recogidas_model->tipos() as $i => $value)
            $tipos[] = array('tipo_id' => $i, 'nombre_tipo' => $value);

        return $tipos;
    }    

   public function listar_entidades_xtipo($id) {
       $empresas = new recogida_entidad();

       return $empresas->search('', $id);
   }
   
   public function anterior_url()
   {
      $url = '';
      
      if($this->offset > 0)
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      
      if( count($this->resultado) == FS_ITEM_LIMIT )
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      }
      
      return $url;
   }
}