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
        $this->pestanya = 'graf';
        //capturo la pestaña si se especifica
        if (isset($_GET['tab'])) {
            $this->pestanya = $_GET['tab'];
        }

        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);        
        
    }
}
