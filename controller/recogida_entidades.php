<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description Recogidas Selectivas
 *
 * @author Zapasoft
 */

require_model('recogida_entidad.php');

class recogida_entidades extends fs_controller
{
    public $recogida_entidad_model;
    public $resultado;
    public $busqueda;


    public function __construct() {
      parent::__construct(__CLASS__, 'Entidades (Ayt/Emp)', 'recogida selectiva', FALSE, TRUE);
      /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
   }

   /**
    * esta función se ejecuta si el usuario ha hecho login,
    * a efectos prácticos, este es el constructor
    */
   protected function process()
   {
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      $this->agente = FALSE;
      $this->busqueda = array(
          'contenido' => '',
          'tipo' => '',
          'orden' => 'entidad_nombre'
      );      
      
      //cargamos nuestro modelo vacio de tabla entidades
      $this->recogida_entidad_model = new recogida_entidad();
      
        //Para editar una entidad
        if (isset($_GET['id'])) {
            $this->template = "recogida_entidad_edita";
            $this->page->title = "Edita ENTIDAD: " . $_GET['id'];
            $this->edita_entidad();
        }
        //PAra añadir Entidad nueva
        else if (isset($_GET['opcion'])) {
            if ($_GET['opcion'] == "nuevaentidad") {
                //$this->page->title = "Nueva Entidad";
                $this->nueva_entidad();
                $this->resultado = $this->recogida_entidad_model->all();   
            }
        }
        //Para eliminar 
        else if (isset($_GET['delete'])) {
            $entidad = $this->recogida_entidad_model->get($_GET['delete']);
            if ($entidad) {
                if ($entidad->delete()) {
                    $this->new_message('Entidad eliminada correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar la entidad.');
            } else
                $this->new_error_msg('Entidad no encontrada.');

            $this->template = "recogida_entidades";
            $this->resultado = $this->recogida_entidad_model->all();
        }
        //Para BUSCAR entidad O FILTRAR
        else if (isset($_POST['filtro_tipo']) || isset($_POST['buscar'])){
            
             if (isset($_POST['buscar'])) {
               $this->busqueda['contenido'] = $_POST['buscar'];
             }
             if (isset($_POST['filtro_tipo'])) {
               $this->busqueda['tipo'] = $_POST['filtro_tipo'];
             }             
            
            $this->resultado = $this->recogida_entidad_model->search($this->busqueda['contenido'], $this->busqueda['tipo']);
        }
        // sino enseña listado con todas 
        else {
            $this->resultado = $this->recogida_entidad_model->all();
            $this->template = "recogida_entidades";
        }
    }
    
    protected function nueva_entidad() {
        //----------------------------------------------
        // agrega una entidad nueva
        //----------------------------------------------
        if(!empty($_POST['entidad_tipo'])){
            $this->recogida_entidad_model->entidad_nombre = $_POST['entidad_nombre'];
            $this->recogida_entidad_model->entidad_telefono = $_POST['entidad_telefono'];
            $this->recogida_entidad_model->entidad_tipo = $_POST['entidad_tipo'];
            $this->recogida_entidad_model->entidad_cifnif = $_POST['entidad_cifnif'];
            $this->recogida_entidad_model->entidad_codpostal = $_POST['entidad_codpostal'];
            
            if ($this->recogida_entidad_model->save()) {
                $this->new_message('Datos de la Entidad guardados correctamente.');
            } else {
                $this->new_error_msg('Imposible guardar los datos de la Entidad.');
                return FALSE;
            }            
        }
        else{
             $this->new_error_msg('Entidad NO creada: Tipo no especificado.');
            return FALSE;
        }
        
    }
    
    protected function edita_entidad() {
        //----------------------------------------------
        // edita una entidad 
        //----------------------------------------------
        $this->resultado = $this->recogida_entidad_model->get($_GET['id']);

        if ($this->resultado) {
            $this->agente = $this->user->get_agente();
        }
        
        if ($this->resultado AND !empty($_POST['entidad_tipo'])) {
            
            $this->resultado->entidad_nombre = $_POST['entidad_nombre'];
            $this->resultado->entidad_telefono = $_POST['entidad_telefono'];
            $this->resultado->entidad_cifnif = $_POST['entidad_cifnif'];
            $this->resultado->entidad_tipo = $_POST['entidad_tipo'];
            $this->resultado->entidad_codpostal = $_POST['entidad_codpostal'];
        
            if ($this->resultado->save()) 
                $this->new_message('Datos Entidad actualizados correctamente.');
            else 
                $this->new_error_msg('Imposible actualizar los datos de la entidad.');            
            
        } elseif (!$this->resultado) {
            $this->new_error_msg('Datos entidad no encontrados.');
        }         
    }
    
    public function listar_tipos() {
        $tipos = array();

        /**
         * En recogida_entidad_model::tipos() nos devuelve un array con todos los estados,
         * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
         */
        foreach ($this->recogida_entidad_model->tipos() as $i => $value)
            $tipos[] = array('entidad_id' => $i, 'nombre_tipo' => $value);

        return $tipos;
    }    
      
}
