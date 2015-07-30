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
    public $pdf_titulo;

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
        $this->recogidas_model = new recogida_empresa();
        
        if (isset($_REQUEST['buscar_proveedor'])) {
            $this->buscar_proveedor();
        }elseif(isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        }elseif(isset ($_POST['direccion_id']) AND $_POST['direccion_id']!=''){
            $this->pdf_filtro_entrada($_POST['codproveedor'], $_POST['direccion_id']);
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

    private function pdf_filtro_entrada($codproveedor = '', $direccion_id = '') {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;
        $nombre_proveedor = $this->recogidas_model->nombre_proveedor($codproveedor);
        $nombre_direccion = $this->recogidas_model->direccion_proveedor($direccion_id);
        $this->pdf_titulo = 'Proveedor: <b>' . $nombre_proveedor . '</b> Dirección: <b>' . $nombre_direccion . '</b>';

        $pdf_doc = new fs_pdf('a4', 'portrait', 'Courier');
        $pdf_doc->pdf->addInfo('Title', 'Recepción Materias Primas ' . $nombre_proveedor . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Subject', 'Recepción Materias Primas ' . $nombre_proveedor . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

        //consulta de materiales hay para este proveedor
        $materiales = $this->recogidas_model->search('', $_POST['dfecha'], $_POST['hfecha'], $_POST['tipo_id'], $codproveedor, $direccion_id, '', TRUE);
        if ($materiales) {
            //Buble para crear documentos para cada material
            foreach ($materiales as $mat) {
                $lineas = $this->recogidas_model->search('', $_POST['dfecha'], $_POST['hfecha'], $_POST['tipo_id'], $codproveedor, $direccion_id, $mat->articulo_id);
                if ($lineas) {
                    //Llamo a la funcion para generar el listado pasandole lineas  
                    $this->genera_pdf($lineas, $pdf_doc, FALSE);
                }
            }
        } else {
            $pdf_doc->pdf->ezText("RECEPCION DE MATERIAS PRIMAS\n", 14, array('aleft' => 180));
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
                    array(
                        'titulo' => $this->pdf_titulo
                    )
            );
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 9,
                        'cols' => array(
                            'titulo' => array('justification' => 'center')
                        ),
                        'shaded' => 0,
                        'width' => 540,
                        'showLines' => 3,
                        'xOrientation' => 'center'
                    )
            );
            $pdf_doc->pdf->ezText("\n", 10);            
            $pdf_doc->pdf->ezText("No existen ENTRADAS para esta Empresa y Direccion...\n", 10);
        }

        $pdf_doc->show();
    }

    /*******************************************************
     * 
     * Funcion que crea los pdf estandar
     * 
     *******************************************************
     *       */    
    private function genera_pdf(&$lineas, &$pdf_doc, $salidas = FALSE) {

        $lineasrecogidas = count($lineas);
        $linea_actual = 0;
        $lppag = 55; /// líneas por página
        $pagina = 1;
        $totalentra = 0;
        $totalsal = 0;

        // Imprimimos las páginas necesarias
        while ($linea_actual < $lineasrecogidas) {
            /// salto de página
            if ($linea_actual > 0) {
                $pdf_doc->pdf->ezNewPage();
                $pdf_doc->pdf->ezText("\n", 8);
                $pagina++;
            }
            /*             * **************************************************************************************************************************************
             * Creamos la cabecera de la página, en este caso para el modelo simple para plantilla
             * 
             * ********************************************************************************************************************************************* */
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
                    array(
                        'titulo' => 'RECEPCION DE MATERIAS PRIMAS'
                    )
            );
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 14,
                        'cols' => array(
                            'titulo' => array('justification' => 'center')
                        ),
                        'shaded' => 0,
                        'width' => 540,
                        'showLines' => 0,
                        'xOrientation' => 'center'
                    )
            );
          
            /*             * **************************************************************************************************************************************
             * Creamos la cabecera de la página, en este caso para el modelo simple para plantilla
             * 
             * ********************************************************************************************************************************************* */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
                    array(
                        'titulo' => $this->pdf_titulo
                    )
            );
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 9,
                        'cols' => array(
                            'titulo' => array('justification' => 'center')
                        ),
                        'shaded' => 0,
                        'width' => 540,
                        'showLines' => 3,
                        'xOrientation' => 'center'
                    )
            );
            $pdf_doc->pdf->ezText("\n", 4);
            /*             * ***************************************************************************************************************************************
             * Creamos la tabla con las lineas del informe :
             * 
             * Fecha    ...
             * ********************************************************************************************************************************************* */
            if ($salidas)
                $header = array(
                    'fecha' => 'Fecha',
                    'material' => 'Material',
                    'entrada' => 'Entrada',
                    'salida' => 'Salida',
                    'ler' => 'Cod. LER',                    
                    'notas' => 'Nota'
                );
            else
                $header = array(
                    'fecha' => 'Fecha',
                    'material' => 'Material',
                    'entrada' => 'Entrada',
                    'ler' => 'Cod. LER',
                    'notas' => 'Nota'
                );

            $pdf_doc->new_table();
            $pdf_doc->add_table_header($header);

            $saltos = 0;
            for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ( $linea_actual < $lineasrecogidas));) {
                $fila = array(
                    'fecha' => date("d/m/Y", strtotime($lineas[$linea_actual]->fecha)),
                    'material' => $lineas[$linea_actual]->nombre_articulo(),
                    'entrada' => $this->show_numero($lineas[$linea_actual]->entrada_empresa, 3),
                    'ler' => $lineas[$linea_actual]->ler_ambiente,
                    'notas' => $lineas[$linea_actual]->notas
                );

                $pdf_doc->add_table_row($fila);
                $totalentra = $totalentra + $lineas[$linea_actual]->entrada_empresa;
                $totalsal = $totalsal + $lineas[$linea_actual]->salida_empresa;
                $saltos++;
                $linea_actual++;
            }
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 8,
                        'cols' => array(
                            'fecha' => array('justification' => 'center', 'width' => 60),
                            'material' => array('justification' => 'center', 'width' => 95),
                            'entrada' => array('justification' => 'right', 'width' => 80),
                            'salida' => array('justification' => 'right', 'width' => 55),
                            'ler' => array('justification' => 'center', 'width' => 85),
                            'notas' => array('justification' => 'left')
                        ),
                        'alignHeadings' => 'center',
                        'width' => 540,
                        'shaded' => 1,
                        'showLines' => 1,
                        'xOrientation' => 'center'
                    )
            );
            /*             * **************************************************
             * 
             * Si es la ultima pagina creamos la tabla de totales
             * 
             */
            if ($linea_actual == count($lineas)) {
                if (!$salidas)
                    $header_totales = array(
                        'col1' => '<b>Totales (' . $linea_actual . '):  </b>',
                        'totalentra' => $this->show_numero($totalentra, 3),
                        'totalsal' => '  ',
                        'col2' => '  '
                    );
                else
                    $header_totales = array(
                        'col1' => '<b>Totales (' . $linea_actual . '):  </b>',
                        'totalentra' => $this->show_numero($totalentra, 3),
                        'totalsal' => $this->show_numero($totalsal, 3),
                        'col2' => '         Diferencia: ' . $this->show_numero($totalentra - $totalsal, 3)
                    );                

                $pdf_doc->new_table();
                $pdf_doc->add_table_header($header_totales);
                $pdf_doc->save_table(
                        array(
                            'fontSize' => 8,
                            'cols' => array(
                                'col1' => array('justification' => 'right', 'width' => 155),
                                'totalentra' => array('justification' => 'right', 'width' => 80),
                                'totalsal' => array('justification' => 'right', 'width' => 80),
                                'col2' => array('justification' => 'left')
                            ),
                            'shaded' => 0,
                            'width' => 540,
                            'showLines' => 4,
                            'xOrientation' => 'center'
                        )
                );
            }

            // Saltamos el cursor en la pagina final para crear footer 
            $pdf_doc->pdf->ezSetY(60);

            /*             * ****************************************************************************************************************************************                        
             * 
             * Creamos el bloque de FOOTER
             * 
             * ************************************************************************************ */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
                    array(
                        'pagina' => 'Pag: ' . $pagina . '/' . ceil(count($lineas) / $lppag),
                        'texto' => 'Email: ' . $this->empresa->email . ' | Tlf: ' . $this->empresa->telefono
                    )
            );
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 8,
                        'cols' => array(
                            'pagina' => array('justification' => 'left'),
                            'texto' => array('justification' => 'right')
                        ),
                        'shaded' => 0,
                        'width' => 540,
                        'showLines' => 4,
                        'xOrientation' => 'center'
                    )
            );
        }
    }
    
    
}
