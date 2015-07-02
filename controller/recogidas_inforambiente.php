<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Caja General
 *
 * @author Zapasoft
 */
require_model('proveedor.php');
require_model('cliente.php');

class recogidas_inforambiente extends fs_controller {

    public $pestanya;
    public $desde;
    public $hasta;
    public $resultados;
    public $allow_delete;
    public $proveedor_direcciones;
    public $proveedor;

    public function __construct() {
        parent::__construct(__CLASS__, 'Informe MedioAmbiente', 'recogida selectiva', FALSE, TRUE);
        /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
    }

    /**
     * esta función se ejecuta si el usuario ha hecho login,
     * a efectos prácticos, este es el constructor
     */
    protected function process() {

        //Pestaña inicial por defecto
        $this->pestanya = 'cert_in';
        //capturo la pestaña si se especifica
        if (isset($_GET['tab'])) {
            $this->pestanya = $_GET['tab'];
        }

        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);

        if (isset($_REQUEST['buscar_proveedor'])) {
            $this->proveedor = new proveedor();
            $this->buscar_proveedor();
        }
    }

    private function buscar_proveedor() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $proveedor = new proveedor();
        $json = array();
        foreach ($proveedor->search($_REQUEST['buscar_proveedor']) as $empre) {
            $json[] = array('value' => $empre->nombre, 'data' => $empre->codproveedor);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_proveedor'], 'suggestions' => $json));
 
        $this->proveedor_direcciones = $proveedor->get_direcciones();
    }

    public function direccion_proveedor(){
        $proveedor = new proveedor();
        $this->proveedor_direcciones = $proveedor->get_direcciones();        
        return $this->proveedor_direcciones;
    }
}
