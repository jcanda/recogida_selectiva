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
require_model('recogida_certificado.php');

class recogidas_inforambiente extends fs_controller {

    public $pestanya;
    public $desde;
    public $hasta;
    public $resultados;
    public $allow_delete;
    public $recogidas_model;
    public $link;

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
        
        $this->recogidas_model = new recogida_certificado();         

        if (isset($_REQUEST['buscar_proveedor'])) {
            $this->buscar_proveedor();
        }elseif(isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        }elseif(isset ($_POST['codproveedor']) OR isset ($_POST['codcliente'])){
            //Genero certificado pdf
            if ($this->genera_pdf())
                // Luego guardo registro si OK
                $this->nuevo_certificado();
            else
                $this->new_error_msg('Error generando PDF del Certificado.');  
        }elseif (isset($_GET['delete_certificado'])){    
            //Eliminar certificado luego enseño
            $certificado = $this->recogidas_model->get($_GET['delete_certificado']);
            if ($certificado) {
                if ($certificado->delete()) {
                    $this->new_message('Certificado eliminado correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar el certificado.');
            } else
                $this->new_error_msg('Certificado no encontrado.');           
        }
        
        //cargamos nuestro modelo vacio de tabla recogidas_certificado  
        if($this->pestanya == 'cert_in')
            $this->resultados = $this->recogidas_model->get_all_in();
        elseif($this->pestanya == 'cert_out')
            $this->resultados = $this->recogidas_model->get_all_out();    
    }

    private function nuevo_certificado() {
        //----------------------------------------------
        // agrega un certificado nuevo
        //----------------------------------------------
                
        //Si la fecha no se detalla se selecciona la de hoy
        if ($_POST['fecha'] == '') {
            $this->recogidas_model->fecha = date('d-m-Y');
        } else
            $this->recogidas_model->fecha = $_POST['fecha'];
        
        //Dependiendo tipo de entrada o salida
        if($_POST['tipo_id'] == 1 AND isset ($_POST['codproveedor']))
            $this->recogidas_model->empresa_id = $_POST['codproveedor'];
        else
            $this->recogidas_model->empresa_id = $_POST['codcliente'];
        
        $this->recogidas_model->n_certificado  = $_POST['n_certificado'];
        $this->recogidas_model->direccion_id = $_POST['direccion_id'];
        $this->recogidas_model->tipo_id = $_POST['tipo_id'];
        $this->recogidas_model->observaciones2 = $_POST['observaciones'];
        
        //Capturo la variable link luego de generar el pdf
        //Nunca llego aqui si no se ejecuto bien el pdf
        $this->recogidas_model->link = $this->link;
        
        if ($this->recogidas_model->save()) {
            $this->new_message('Datos del Certificado guardados correctamente.');
        } else {
            $this->new_error_msg('Imposible guardar los datos del nuevo Certificado.');
            return FALSE;
        }        
    }
    
    private function genera_pdf() {
        //filtro si ENTRADA o SALIDA (TIPO_ID)
        //Capturo datos de DESDE y HASTA y  CONSULTO para lineas que me interesan
        //CONSULTO los datos de la EMPRESA y los de la DIRECCION
        // El resto de variasbles las cojo del POST N_certficado, FECHA
        return TRUE;  
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
