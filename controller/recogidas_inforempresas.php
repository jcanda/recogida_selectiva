<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of recogidas Medioambiente
 *
 * @author Zapasoft
 */

require_once 'plugins/recogida_selectiva/extras/fs_pdf.php';

require_model('proveedor.php');
require_model('cliente.php');
require_model('recogida_empresa.php');

class recogidas_inforempresas extends fs_controller {

    public $pestanya;
    public $desde;
    public $hasta;
    public $resultados;
    public $allow_delete;
    public $recogidas_model;
    public $filename;
    public $link;
    public $autorizaciones;

    public function __construct() {
        parent::__construct(__CLASS__, 'Informes Empresa', 'recogida selectiva', FALSE, TRUE);
        /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
    }

    /**
     * esta función se ejecuta si el usuario ha hecho login,
     * a efectos prácticos, este es el constructor
     */
    protected function process() {
        //Pestaña inicial por defecto
        $this->pestanya = 'list';
        //capturo la pestaña si se especifica
        if (isset($_GET['tab'])) {
            $this->pestanya = $_GET['tab'];
        }

        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);  
        
        $this->desde = Date('1-m-Y');
        $this->hasta = Date('d-m-Y', mktime(0, 0, 0, date("m") + 1, date("1") - 1, date("Y")));        
        
        if (isset($_REQUEST['buscar_proveedor'])) {
            $this->buscar_proveedor();
        }elseif(isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        }        
        
    }
    
    private function buscar_proveedor() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $proveedor = new proveedor();
        $json = array();
        foreach ($proveedor->search($_REQUEST['buscar_proveedor']) as $empre) {
            $json[] = array('value' => $empre->nombre, 'data' => $empre->codproveedor, 'direcciones' => json_encode( $empre->get_direcciones()));
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_proveedor'], 'suggestions' => $json));
    }

    private function buscar_cliente() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $cliente = new cliente();
        $json = array();
        foreach ($cliente->search($_REQUEST['buscar_cliente']) as $empre) {
            $json[] = array('value' => $empre->nombre, 'data' => $empre->codcliente, 'direcciones' => json_encode( $empre->get_direcciones()));
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
    }    
}
