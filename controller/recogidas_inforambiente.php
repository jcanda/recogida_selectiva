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
require_model('recogida_certificado.php');
require_model('recogida_autorizacion.php');
require_model('recogida_doc.php');

class recogidas_inforambiente extends fs_controller {

    public $pestanya;
    public $desde;
    public $hasta;
    public $resultados;
    public $allow_delete;
    public $allow_outano;
    public $recogidas_model;
    public $filename;
    public $link;
    public $autorizaciones;
    public $ano;
    public $fecha;
    public $doc_residuos;

    public function __construct() {
        parent::__construct(__CLASS__, 'Certificados M. Ambiente', 'recogida selectiva', FALSE, TRUE);
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
        
        //Cargamos modelo vacio
        $this->recogidas_model = new recogida_certificado();
        
        //Año por defecto el actual y fecha actual
        $this->allow_outano = TRUE;
        $this->ano = date ("Y");
        $this->fecha = date("d-m-Y");
        //Capturo año si se especifica
        if (isset($_POST['ano'])) {
            $this->ano = $_POST['ano'];           
        }
               
        // ¿el año esta cerrado?
        if($this->ano != date ("Y")){
            // Cargo configuracion de si año cerrado o no
            $this->allow_outano = TRUE;
            
            $this->new_advice('OJO! El año seleccionado no coincide con el año actual...');
            if($this->ano != date ("Y",strtotime ($_POST['fecha'])) AND ($_POST['codproveedor']>0 OR $_POST['codcliente']>0))
                    $this->new_error_msg('Fecha del certificado no esta dentro del año seleccionado...');
                    
            if(!$this->allow_outano)
                $this->new_error_msg('Año cerrado: no se permite la realizacion de nuevos certificados en este año.');
        }
        
        $this->link = "https://gestion.luisrivas.es/tmp/".FS_TMP_NAME."certificados/";
        
        if (isset($_REQUEST['buscar_proveedor'])) {
            $this->buscar_proveedor();
        }elseif(isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        }elseif($this->ano == date ("Y",strtotime ($_POST['fecha'])) AND $this->allow_outano AND ($_POST['codproveedor']>0 OR $_POST['codcliente']>0 )){
            //Genero certificado pdf
            if ($this->genera_pdf())
                // Luego guardo registro si OK
                $this->nuevo_certificado();

        }elseif (isset($_GET['delete_certificado'])){    
            //Eliminar certificado luego enseño
            $certificado = $this->recogidas_model->get($_GET['delete_certificado']);
            
            if ($certificado) {                
                unlink('tmp/'.FS_TMP_NAME.'certificados/'.$certificado->link);
                //Liberamos denuevo las lineas de recogidas_empresas
                $recog_empresa = new recogida_empresa();
                $lineas_empresa = $recog_empresa->get_lineas_cert($certificado->n_certificado);
                
                foreach ($lineas_empresa as $linea){
                    $linea->n_cert_recogida = NULL;
                    $linea->save();
                }
                //Ahora finalmente eliminamos el reg. del certificado
                if ($certificado->delete()) {
                    $this->new_message('Certificado eliminado correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar el certificado.');
            } else
                $this->new_error_msg('Certificado no encontrado.');           
        }elseif ($_POST['residuos']!= '' AND $_POST['direccion_id']!= '' AND $this->pestanya == 'just'){ 
            
            $this->pdf_aceptacion($_POST['codproveedor'], $_POST['direccion_id'], $_POST['residuos']);
        }
        
        //cargamos nuestro modelo vacio de tabla recogidas_certificado  
        if($this->pestanya == 'cert_in'){
            $this->resultados = $this->recogidas_model->get_all_in($this->ano);
            //Cargamos listado de autorizaciones
            $autorizacion = new recogida_autorizacion ();
            $this->autorizaciones = $autorizacion->get_all();        
        }elseif($this->pestanya == 'cert_out'){
            $this->resultados = $this->recogidas_model->get_all_out($this->ano);
        }elseif($this->pestanya == 'just'){
            $doc_residuos = new recogida_doc();
            $this->doc_residuos = $doc_residuos->get_all(TRUE);
        }
    }

    private function pdf_aceptacion($codproveedor = '', $direccion_id = '', $residuos = '') {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;
        $proveedor = new proveedor();
        $proveedor_select = $proveedor->get($codproveedor);
                
        $direccion = new direccion_proveedor();
        $direccion_select = $direccion->get($direccion_id);
                
        $doc_residuos = new recogida_doc();
        $doc_residuos_select = $doc_residuos->get($residuos);
        //Ahora consultamos todos los LER y todas las Descripciones
        $doc_residuos_lers = $doc_residuos->search($doc_residuos_select->autorizacion, $doc_residuos_select->tipo_material);
        //Ahora las convertimos el array a texto
        foreach ($doc_residuos_lers as $lers){
            $lers_array[] = $lers->ler;
            $descrip_array[] = $lers->descripcion;
        }
        $lers_comas = implode(", ", $lers_array);
        $descrip_comas = implode(", ", $descrip_array);
        
        $pdf_doc = new fs_pdf();
        $pdf_doc->pdf->selectFont('plugins/recogida_selectiva/extras/ezpdf/fonts/FreeSerif.afm');
        $pdf_doc->pdf->addInfo('Title', 'Documento Aceptación '.$proveedor_select->nombre .' Dirección: '.$direccion_select->direccion);
        $pdf_doc->pdf->addInfo('Subject', 'Documento Aceptación ' . $proveedor_select->nombre . ' Dirección: ' . $direccion_select->direccion);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
            /************************************************************************************************************************************************
             * 
             * CABECERA
             * 
             * ********************************************************************************************************************************************* */
        $pdf_doc->new_table();
        $pdf_doc->add_table_header(
                array(
                    'titulo' => "<b>DOCUMENTO DE ACEPTACIÓN DE RESIDUOS</b>"
                )
        );
        $pdf_doc->save_table(
                array(
                    'fontSize' => 16,
                    'cols' => array(
                        'titulo' => array('justification' => 'center')
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'showLines' => 3,
                    'xOrientation' => 'center',
                    'showHeadings' => 1
                )
        );
        $pdf_doc->pdf->ezText("\n", 8);
        
            /************************************************************************************************************************************************
             * 
             * BLOQUE 1 TITULO
             * 
             * ********************************************************************************************************************************************* */
        $pdf_doc->new_table();
        $pdf_doc->add_table_header(
                array(
                    'titulo' => "DATOS DEL PRODUCTOR"
                )
        );
        $pdf_doc->save_table(
                array(
                    'fontSize' => 11,
                    'cols' => array(
                        'titulo' => array('justification' => 'center')
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'showLines' => 1,
                    'xOrientation' => 'center'
                )
        );
            /************************************************************************************************************************************************
             * 
             * BLOQUE 2 DATOS PRODUCTOR
             * 
             * ********************************************************************************************************************************************* */
        
        $pdf_doc->new_table();
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "RAZÓN SOCIAL: ",
                    'datos' => $proveedor_select->nombre,
                    'col3' => ''
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "C.I.F.: ",
                    'datos' => $proveedor_select->cifnif,
                    'col3' => ''
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "DIRECCIÓN: ",
                    'datos' => $direccion_select->direccion,
                    'col3' => ''
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => $direccion_select->codpostal.'        '.$direccion_select->ciudad. '                                        '.$direccion_select->provincia,
                    'col3' => ''
                )
        );    
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "TELÉFONOS: ",
                    'datos' => $proveedor_select->telefono1. '        '.$proveedor_select->telefono2,
                    'col3' => ''
                )
        );      
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "CORREO: ",
                    'datos' => $proveedor_select->email,
                    'col3' => ''
                )
        );  
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => ' ',
                    'col3' => ' '
                )
        );        
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => ' ',
                    'col3' => ' '
                )
        );
        $pdf_doc->save_table(
                array(
                    'fontSize' => 10,
                    'cols' => array(
                        'titulo' => array('justification' => 'left', 'width' => 100),
                        'datos' => array('justification' => 'left', 'width' => 400),
                        'col3' => array('justification' => 'center', 'width' => 40)
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'gridlines'=> EZ_GRIDLINE_TABLE,
                    'xOrientation' => 'center'
                )
        ); 
        
        $pdf_doc->pdf->ezText("\n", 4);
        
            /************************************************************************************************************************************************
             * 
             * BLOQUE 3 TITULO RESIDUO
             * 
             * ********************************************************************************************************************************************* */
        $pdf_doc->new_table();
        $pdf_doc->add_table_header(
                array(
                    'titulo' => "DATOS DEL RESIDUO"
                )
        );
        $pdf_doc->save_table(
                array(
                    'fontSize' => 11,
                    'cols' => array(
                        'titulo' => array('justification' => 'center')
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'showLines' => 1,
                    'xOrientation' => 'center'
                )
        );
            /************************************************************************************************************************************************
             * 
             * BLOQUE 4 DATOS RESIDUO
             * 
             * ********************************************************************************************************************************************* */        
        $pdf_doc->new_table();
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "CODIGO L.E.R. (Según Orden MAM/304/2002) ",
                    'datos' => $lers_comas,
                    'col3' => ' '
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "Descripción: ",
                    'datos' => $descrip_comas,
                    'col3' => ' '
                )
        );    
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => ' ',
                    'col3' => ' '
                )
        );        
        $pdf_doc->save_table(
                array(
                    'fontSize' => 10,
                    'cols' => array(
                        'titulo' => array('justification' => 'left', 'width' => 230),
                        'datos' => array('justification' => 'left', 'width' => 270),
                        'col3' => array('justification' => 'center', 'width' => 40)
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'gridlines'=> EZ_GRIDLINE_TABLE,
                    'xOrientation' => 'center'
                )
        ); 
        $pdf_doc->pdf->ezText("\n", 4);    
            /************************************************************************************************************************************************
             * 
             * BLOQUE 5 TITULO GESTOR
             * 
             * ********************************************************************************************************************************************* */
        $pdf_doc->new_table();
        $pdf_doc->add_table_header(
                array(
                    'titulo' => "DATOS DEL GESTOR"
                )
        );
        $pdf_doc->save_table(
                array(
                    'fontSize' => 11,
                    'cols' => array(
                        'titulo' => array('justification' => 'center')
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'showLines' => 1,
                    'xOrientation' => 'center'
                )
        );        

            /************************************************************************************************************************************************
             * 
             * BLOQUE 2 DATOS GESTOR
             * 
             * ********************************************************************************************************************************************* */
        if ($_POST['almacen'] == '0002086')
            $direccion_gestor = 'Avd. Peirao Besada, 45 (36005) POIO';
        else
            $direccion_gestor = 'Rúa As Mámoas, 41 Parc-B81 (36158) MARCÓN'; 
        
        $pdf_doc->new_table();
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "RAZÓN SOCIAL: ",
                    'datos' => $this->empresa->nombre,
                    'col3' => ''
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "C.I.F.: ",
                    'datos' => $this->empresa->cifnif,
                    'col3' => ''
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "Nº DE AUTORIZACIÓN: ",
                    'datos' => $doc_residuos_select->autorizacion,
                    'col3' => ''
                )
        );        
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "DOMICILIO DEL CENTRO: ",
                    'datos' => $direccion_gestor,
                    'col3' => ''
                )
        );
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => ' PONTEVEDRA',
                    'col3' => ''
                )
        );    
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "TELÉFONOS: ",
                    'datos' => $this->empresa->telefono. '             FAX: '.$this->empresa->fax,
                    'col3' => ''
                )
        );      
        $pdf_doc->add_table_row(
                array(
                    'titulo' => "CORREO: ",
                    'datos' => $this->empresa->email,
                    'col3' => ''
                )
        );  
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => ' ',
                    'col3' => ' '
                )
        );        
        $pdf_doc->add_table_row(
                array(
                    'titulo' => " ",
                    'datos' => ' ',
                    'col3' => ' '
                )
        );
        $pdf_doc->save_table(
                array(
                    'fontSize' => 10,
                    'cols' => array(
                        'titulo' => array('justification' => 'left', 'width' => 150),
                        'datos' => array('justification' => 'left', 'width' => 350),
                        'col3' => array('justification' => 'center', 'width' => 40)
                    ),
                    'shaded' => 0,
                    'width' => 540,
                    'gridlines'=> EZ_GRIDLINE_TABLE,
                    'xOrientation' => 'center'
                )
        );      
        
       $pdf_doc->pdf->ezText("\n", 6);  
       $pdf_doc->pdf->ezText('En Pontevedra, a  ' . date("d"). ' de '.date("M").' de '.date("Y"), 10, array('aleft' => 400));
       $pdf_doc->pdf->ezText('Sello y firma', 8, array('aleft' => 450));
        
        //FINALMENTE ENSEÑAMOS EL PDF
        $pdf_doc->show();
    }

    private function nuevo_certificado() {
        //----------------------------------------------
        // agrega un certificado nuevo
        //----------------------------------------------
        
        //Cargamos modelo vacio
        $this->recogidas_model = new recogida_certificado();  
         
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
        $this->recogidas_model->link = $this->filename;
        
        if ($this->recogidas_model->save()) {
            $this->new_message('Datos del Certificado guardados correctamente.');
        } else {
            $this->new_error_msg('Imposible guardar los datos del nuevo Certificado.');
            return FALSE;
        }        
    }
    
    private function genera_pdf() {
        // Primero chequeo variables y compruebo numero certificado
        if ($this->algun_error()) {
            return FALSE;
        } elseif ($this->recogidas_model->existe_certificado($_POST['n_certificado'], $_POST['tipo_id'], $this->ano)) {
            $this->new_error_msg('Número de certificado no especificado o YA EXISTENTE...');
            return FALSE;
        } else {
            // ************************************************************************
            // ENTRADA
            // Creamos el PDF y escribimos sus metadatos de ENTRADA
            // CONSULTO los datos de la EMPRESA y los de la DIRECCION
            // El resto de variasbles las cojo del POST N_certficado, FECHA
            //*************************************************************************
            if ($_POST['tipo_id'] == 1) {
                $almacen = $_POST['almacen'];
                $this->filename = 'certificado_productor'.$almacen.$this->ano.$this->zerofill($_POST['n_certificado'], 6) . '.pdf';
                
                $proveedor = new proveedor();
                $proveedor_select = $proveedor->get($_POST['codproveedor']);
                
                $direccion = new direccion_proveedor();
                $direccion_select = $direccion->get($_POST['direccion_id']);
                
                $autorizacion = new recogida_autorizacion();
                $autorizacion_select = $autorizacion->get($_POST['n_autorizacion']);
                
                $pdf_doc = new fs_pdf();
                $pdf_doc->pdf->selectFont('plugins/recogida_selectiva/extras/ezpdf/fonts/Times-Roman.afm');
                $pdf_doc->pdf->ezSetMargins(2, 1, 1, 1);
                $pdf_doc->pdf->addInfo('Title', 'Certificado ' . $almacen .$this->zerofill($_POST['n_certificado'], 6));
                $pdf_doc->pdf->addInfo('Subject', 'Certificado para Productor - '. $almacen . $this->zerofill($_POST['n_certificado'], 6));
                $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
              
                //Capturo datos de DESDE y HASTA y  CONSULTO para lineas que me interesan
                //Cargamos modelo vacio 
                $lineas = '';
                $this->recogidas_model = new recogida_certificado();
                $lineas = $this->recogidas_model->lineas_certificado($_POST['desde'], $_POST['hasta'], $_POST['tipo_id'], $proveedor_select->codproveedor, $direccion_select->id);

                if ($lineas) {
                    $lineasrecogidas = count($lineas);
                    $linea_actual = 0;
                    $lppag = 15; /// líneas por página
                    $pagina = 1;

                    // Imprimimos las páginas necesarias
                    while ($linea_actual < $lineasrecogidas) {
                        /// salto de página
                        if ($linea_actual > 0) {
                            $pdf_doc->pdf->ezNewPage();
                            $pdf_doc->pdf->ezText("\n", 10);
                            $pagina++;
                        }
                        /* ******************************************************************************************************************************************
                         * Creamos la cabecera de la página, en este caso para el modelo simple para plantilla
                         * 
                         * ********************************************************************************************************************************************* */
                        //añado lineas en coordenadas exactas
                        $pdf_doc->pdf->ezText('Tfno: ' . $this->empresa->telefono, 8, array('aleft' => 480));
                        $pdf_doc->pdf->ezText('Email: ' . $this->empresa->email, 8, array('aleft' => 480));
                        $pdf_doc->pdf->ezText("\n", 4);

                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'titulo' => '<b>COMPROBANTE DE ENTREGA Nº:</b>',
                                    'codigo' => '<b>TNP30360'.$almacen . $this->ano . $this->zerofill($_POST['n_certificado'], 6) . '</b>'
                                )
                        );
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 11,
                                    'cols' => array(
                                        'titulo' => array('justification' => 'left'),
                                        'codigo' => array('justification' => 'right')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'gridlines' => EZ_GRIDLINE_TABLE,
                                    'xOrientation' => 'center'
                                )
                        );
                        //Siguiente bloque de tipo texto
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_row(
                                array('texto' =>  'Estimado proveedor:
                                    ')
                        );
                        $pdf_doc->add_table_row(
                                array(
                                    'texto' =>  'Con la publicación del Decreto 59/2009, de 26 de febrero, por el que se regula la trazabilidad de los residuos, los gestores de residuos no peligrosos están obligados a documentar cada una de las entregas de residuos mediante un comprobante que deben enviar al productor, en el que figuren como mínimo los siguientes datos:
                                    ')
                        );
                        $pdf_doc->add_table_row(
                                array(
                                    'texto' =>  '-Identificación del centro remitente del residuo (la persona productora o gestora del que procede).
-Características de los residuos.
-Identificación de la persona gestora destinataria y del tipo de gestión que va a realizar.
-Fecha de entrega de los residuos y firma de la persona gestora.
')
                        );                        
                        $pdf_doc->add_table_row(
                                array(
                                    'texto' =>  'Por este motivo les enviamos el presente certificado con los residuos que hemos recibido en nuestras instalaciones, provenientes de su empresa:')
                        ); 
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 10,
                                    'cols' => array(
                                        'texto' => array('justification' => 'left')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'showLines' => 0,
                                    'xOrientation' => 'center'
                                )
                        );
                        
                        $pdf_doc->pdf->ezText("\n", 10);
                        
                        /* ******************************************************************************************************************************************                        
                         * 
                         * Creamos el bloque de los DAtos del PRODUCTOR
                         * 
                         **************************************************************************************/
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'titulo' => '<b>DATOS DEL PRODUCTOR</b>'
                                )
                        );
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 10,
                                    'cols' => array(
                                        'titulo' => array('justification' => 'center')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'showLines' => 0,
                                    'xOrientation' => 'center'
                                )
                        );

                        /* ******************************************************************************************************************************************                        
                         * 
                         * Creamos el bloque de los INFORMACION del PRODUCTOR
                         * 
                         **************************************************************************************/
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'nombre' => 'NOMBRE',
                                    'direccion' => 'DIRECCIÓN',
                                    'cif' => 'C.I.F',
                                    'productor' => 'PRODUCTOR'
                                )
                        );
                        $pdf_doc->add_table_row(
                                array(
                                    'nombre' => $proveedor_select->nombre,
                                    'direccion' => $direccion_select->direccion,
                                    'cif' => $proveedor_select->cifnif,
                                    'productor' => ''
                                )
                        );                        
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 8,
                                    'cols' => array(
                                        'nombre' => array('justification' => 'left'),
                                        'direccion' => array('justification' => 'left'),
                                        'cif' => array('justification' => 'left'),
                                        'productor' => array('justification' => 'center'),
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'showLines' => 1,
                                    'xOrientation' => 'center'
                                )
                        );
                        
                        $pdf_doc->pdf->ezText("\n", 10);
                        
                        /* ******************************************************************************************************************************************                        
                         * 
                         * Creamos el bloque de los CARACTERÍSTICAS DE LOS RESIDUOS
                         * 
                         **************************************************************************************/
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'titulo' => '<b>CARACTERÍSTICAS DE LOS RESIDUOS</b>'
                                )
                        );
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 10,
                                    'cols' => array(
                                        'titulo' => array('justification' => 'center')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'showLines' => 0,
                                    'xOrientation' => 'center'
                                )
                        );                        
                        
                        /* ******************************************************************************************************************************************
                         * Creamos la tabla con las lineas del certificado :
                         * 
                         * Fecha    LER  Codigo_Operacion   Descripcion    Obserbaciones Cantidad
                         * ********************************************************************************************************************************************* */
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'fecha' => 'Fecha',
                                    'ler' => 'Cód. LER',
                                    'codigo_operacion' => 'Cód. Operación',
                                    'descripcion' => 'Descripción',
                                    'notas' => 'Nota</b>',
                                    'cantidad' => 'Cantidad (kg)'
                                )
                        );

                        $saltos = 0;
                        for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ( $linea_actual < $lineasrecogidas));) {
                            $fila = array(
                                'fecha' => date("d/m/Y", strtotime($lineas[$linea_actual]->fecha)),
                                'ler' => $lineas[$linea_actual]->ler_ambiente,
                                'codigo_operacion' => $autorizacion_select->cod_operacion,
                                'descripcion' => '  ' . $this->fix_html($lineas[$linea_actual]->descrip_ambiente),
                                'notas' => $this->fix_html($lineas[$linea_actual]->notas),
                                'cantidad' => $this->show_numero($lineas[$linea_actual]->entrada_empresa, 2)
                            );

                            $pdf_doc->add_table_row($fila);
                            $saltos++;
                            $linea_actual++;
                        }
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 8,
                                    'cols' => array(
                                        'fecha' => array('justification' => 'center', 'width' => 60),
                                        'ler' => array('justification' => 'center', 'width' => 50),
                                        'codigo_operacion' => array('justification' => 'center', 'width' => 90),
                                        'descripcion' => array('justification' => 'left', 'width' => 180),
                                        'notas' => array('justification' => 'left'),
                                        'cantidad' => array('justification' => 'right', 'width' => 60)
                                    ),
                                    'alignHeadings' => 'center',
                                    'width' => 540,
                                    'shaded' => 1,
                                    'showLines' => 1,
                                    'xOrientation' => 'center'
                                )
                        );

                        /* *******************************************************************************************************************************************
                         * 
                         * Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
                         * 
                         * ********************************************************************************************************************************************* */
                        if ($_POST['observaciones'] == '') {
                            $salto = "\n";
                        } else {
                            $salto = "      <b>Observaciones</b>: " . $this->fix_html($_POST['observaciones']) . "\n";
                            $saltos += count(explode("\n", $_POST['observaciones'])) - 1;
                        }

                        if ($saltos < $lppag) {
                            //Numero de saltos a rellenar hasta el final de la pagina
                            for (; $saltos < $lppag; $saltos++)
                                $salto2 .= "\n";
                            $pdf_doc->pdf->ezText($salto2, 10);

                            //Pongo las observaciones al final               
                            $pdf_doc->pdf->ezText($salto, 8);
                        } else if ($linea_actual >= $lineasfact) {
                            $pdf_doc->pdf->ezText($salto, 8);
                        } else
                        //Salto al final de cada pagina completa
                            $pdf_doc->pdf->ezText("\n", 9);
                        
                        /* ******************************************************************************************************************************************                        
                         * 
                         * Creamos el bloque de los DATOS DE GESTOR
                         * 
                         **************************************************************************************/
                        $pdf_doc->set_y(280);
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'titulo' => '<b>DATOS DEL GESTOR</b>'
                                )
                        );
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 10,
                                    'cols' => array(
                                        'titulo' => array('justification' => 'center')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'showLines' => 0,
                                    'xOrientation' => 'center'
                                )
                        );                          
                        /* ******************************************************************************************************************************************                        
                         * 
                         * Creamos el bloque INFORMACION DE GESTOR
                         * 
                         **************************************************************************************/
                        if ($almacen == '0002086')
                            $direccion_gestor = 'Avd. Peirao Besada, 45 (36005) POIO';
                        else
                            $direccion_gestor = 'Rúa As Mámoas, 41 Parc-B81 (36158) MARCÓN';
                        
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_header(
                                array(
                                    'col1' => 'NOMBRE:',
                                    'col2' => 'LUIS RIVAS, S.L.'
                                )
                        );
                        $pdf_doc->add_table_row(
                                array(
                                    'col1' => 'CIF:',
                                    'col2' => 'B-36.044.899'
                                )
                        );
                        $pdf_doc->add_table_row(
                                array(
                                    'col1' => 'Nº DE AUTORIZACIÓN:',
                                    'col2' => $autorizacion_select->autorizacion
                                )
                        );
                        $pdf_doc->add_table_row(
                                array(
                                    'col1' => 'DIRECCIÓN:',
                                    'col2' => $direccion_gestor
                                )
                        );                         
                        $pdf_doc->add_table_row(
                                array(
                                    'col1' => 'RESPONSABLE:',
                                    'col2' => 'Luis Rivas García'
                                )
                        ); 
                        $pdf_doc->add_table_row(
                                array(
                                    'col1' => 'FIRMA Y SELLO:',
                                    'col2' => '
                                    





'
                                    
                                )
                        );                         
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 9,
                                    'cols' => array(
                                        'col1' => array('justification' => 'left'),
                                        'col2' => array('justification' => 'left')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'gridlines' => EZ_GRIDLINE_ALL,
                                    'xOrientation' => 'center'
                                )
                        );                     
                        
                        $pdf_doc->pdf->ezText("\n", 10);
                        $pdf_doc->pdf->ezText("Sin otro particular, reciba un cordial saludo.", 10, array('aleft' => 30));
                        /* ******************************************************************************************************************************************                        
                         * 
                         * Creamos el bloque de PROTECCION DE DATOS
                         * 
                         **************************************************************************************/
                        $pdf_doc->new_table();
                        $pdf_doc->add_table_row(
                                array(
                                    'texto' => 'En cumplimiento de la  LOPD, se advierte que los datos de carácter  personal se incluirán en los ficheros creados por LUIS RIVAS, S.L.    La finalidad de los ficheros es la correcta gestión de las relaciones comerciales y administrativas con los clientes derivados de la actividad de la empresa,   comercio   al mayor de chatarra y materiales de desecho, actualmente y en el futuro. El afectado podrá acceder a sus datos personales, rectificarlos o en su caso cancelarlos en LUIS RIVAS, S.L, Avenida Peirao Besada, 45 – 36005 – POIO (PONTEVEDRA); Telf.: 986 872 864, e-mail: info@luisrivas.es  como responsable del fichero.'
                                )
                        );
                        $pdf_doc->save_table(
                                array(
                                    'fontSize' => 6,
                                    'cols' => array(
                                        'texto' => array('justification' => 'full')
                                    ),
                                    'shaded' => 0,
                                    'width' => 540,
                                    'showLines' => 0,
                                    'xOrientation' => 'center'
                                )
                        );                          
                    
                        /// ¿Añadimos la firma?
                        if( file_exists('plugins/recogida_selectiva/view/img/firma_luis.png') )
                        {
                            $pdf_doc->pdf->addPngFromFile('plugins/recogida_selectiva/view/img/firma_luis.png', 350,117,131,65);
                        }        
                    }
                }else {
                    $this->new_error_msg("No existen recogidas disponibles para este Productor entre estas fechas");
                    return FALSE;
                }
                //Guardamos el archivo pdf
                if ($this->filename) {
                    if (!file_exists('tmp/' . FS_TMP_NAME . 'certificados'))
                        mkdir('tmp/' . FS_TMP_NAME . 'certificados');

                    if ($pdf_doc->save('tmp/' . FS_TMP_NAME . 'certificados/' . $this->filename))
                            $this->new_message('PDF generado ' . $this->filename);
                    
                    //Finalmente Marcamos las lineas de recogida_empresa como incluidas
                    foreach ($lineas as $linea){
                        $linea->n_cert_recogida = $_POST['n_certificado'];
                        $linea->save();
                    }
                    
                    return TRUE;
                }
                
                // ***********************************************************
                // SALIDA
                // Creamos el PDF y escribimos sus metadatos de SALIDA 
                //    
            } elseif ($_POST['tipo_id'] == 2) {
                $this->filename = 'certificado_gestor' . $this->ano . $this->zerofill($_POST['n_certificado'], 7) . '.pdf';

                $pdf_doc = new fs_pdf();
                $pdf_doc->pdf->selectFont('plugins/recogida_selectiva/extras/ezpdf/fonts/Times-Roman.afm');
                $pdf_doc->pdf->ezSetMargins(2, 1, 1, 1);
                $pdf_doc->pdf->addInfo('Title', 'Certificado ' . $this->zerofill($_POST['n_certificado'], 7));
                $pdf_doc->pdf->addInfo('Subject', 'Certificado para Gestor - ' . $this->zerofill($_POST['n_certificado'], 7));
                $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

                //Capturo datos de DESDE y HASTA y  CONSULTO para lineas que me interesan
                $lineas = $this->recogidas_model->lineas_certificado($_POST['desde'], $_POST['hasta'], $_POST['tipo_id'], $_POST['codcliente']);

                if ($lineas) {
                    return TRUE;                    
                }

            }
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
    
    private function algun_error(){
        $status = TRUE;
        
        if(!isset($_POST['direccion_id']))
            $this->new_error_msg("Empresa o Direccion no especificada");             
        elseif($_POST['desde'] == '' OR $_POST['hasta'] == '')
            $this->new_error_msg("Intervalos de Fechas no especificados");
        elseif($_POST['n_certificado']=='' OR $_POST['n_certificado']==0)
            $this->new_error_msg("Numero de certificado no especificado");
        else
            $status = FALSE;
      
      return $status;
    }
    
    private function zerofill($valor, $longitud){
        $res = str_pad($valor, $longitud, '0', STR_PAD_LEFT);
        return $res;
    }    
    
    private function fix_html($txt)
    {    
      $newt = str_replace('&lt;', '<', $txt);
      $newt = str_replace('&gt;', '>', $newt);
      $newt = str_replace('&quot;', '"', $newt);
      $newt = str_replace('&#39;', "'", $newt);
      return $newt;
    }
    
}
