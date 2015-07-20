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
require_once 'plugins/recogida_selectiva/extras/fs_pdf.php';
require_model('recogida_diario.php');

class recogidas_inforayunta extends fs_controller {

    public $pestanya;    
    public $desde;
    public $hasta;
    public $recogidas_model;
    public $pdf_titulo;

    public function __construct() {
        parent::__construct(__CLASS__, 'Informes Ayuntamiento', 'recogida selectiva', FALSE, TRUE);
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
        $this->desde = Date('1-m-Y');
        $this->hasta = Date('d-m-Y', mktime(0, 0, 0, date("m") + 1, date("1") - 1, date("Y")));
        $this->recogidas_model = new recogida_diario();
                        
        if (isset($_POST['listado'])) {
            if ($_POST['listado'] == 'recogidas_filtro') {
                if ($_POST['generar'] == 'pdf') {
                    if ($_POST['filtro'] == 'Ecovidrio'){
                        $this->pdf_filtro_material(3);
                    }elseif($_POST['filtro'] == 'materiales'){
                        $this->pdf_filtro_material();                       
                    }elseif($_POST['filtro'] == 'ayuntamientos'){
                        $this->pdf_filtro_ayunts();
                    }elseif($_POST['filtro'] == 'empresas'){
                        $this->pdf_filtro_empresa();                      
                    }else{
                        $this->pdf_filtro_empresa($_POST['filtro']);
                    }
                } else
                    $this->csv_recogidas_filtro();
            }
            else {
                if ($_POST['generar'] == 'pdf') {
                    $this->pdf_recogidas_listado();
                } else
                    $this->csv_recogidas_listado();
            }
        }
    }
    
    private function pdf_filtro_empresa($empresa = '', $material = '') {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        $pdf_doc = new fs_pdf('a4', 'portrait', 'Courier');
        $pdf_doc->pdf->addInfo('Title', 'Recogidas Ayunts ' . $empresa . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Subject', 'Recogidas Ayunts' . $empresa . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

        //consulta de empresas 
        $empresas_all = $this->recogidas_model->search_empresa($empresa);
        //consulta de materiales hay
        if ($material != '')
            $materiales = array($material);
        else
            $materiales = array(1, 2, 3);
        
        // Bucle para cada Empresa si no se especifica una
        foreach ($empresas_all as $empre) {
            //Buble para crear documentos para cada material
            foreach ($materiales as $mat) {
                $lineas = $this->recogidas_model->search($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat);
                if ($lineas) {
                    $pap_sum = $this->recogidas_model->sumabytipo($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat, 2);
                    $iglu_sum = $this->recogidas_model->sumabytipo($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat, 1);
                    $this->pdf_titulo = 'Recogidas ' . $empre->entidad_nombre . ': ' . $this->recogidas_model->nombre_material($mat) . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha'];
                    //Llamo a la funcion para generar el listado pasandole lineas 
                    $this->genera_pdf($lineas, $pdf_doc, FALSE, $pap_sum, $iglu_sum);
                }
            }
        }
        $pdf_doc->show();
    }

    private function pdf_filtro_material($material = '', $empresa = '') {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        $pdf_doc = new fs_pdf('a4', 'portrait', 'Courier');
        $pdf_doc->pdf->addInfo('Title', 'Recogidas Ayunts ' . $_POST['filtro'] . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Subject', 'Recogidas Ayunts' . $_POST['filtro'] . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
        
        //consulta de materiales hay
        if ($material != '')
            $materiales = array($material);
        else
            $materiales = array(1, 2, 3);
        //consulta de empresas 
        $empresas_all = $this->recogidas_model->search_empresa($empresa);
        
        // Bucle para cada Material si no se especifica uno
        foreach ($materiales as $mat) {
            //Bucle para cada empresa dentro del Material
            foreach ($empresas_all as $empre) {
                $lineas = $this->recogidas_model->search($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat);
                if ($lineas) {
                    $pap_sum = $this->recogidas_model->sumabytipo($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat, 2);
                    $iglu_sum = $this->recogidas_model->sumabytipo($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat, 1);
                    $this->pdf_titulo = 'Recogidas ' . $this->recogidas_model->nombre_material($mat) . ': ' . $empre->entidad_nombre . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha'];
                    //Llamo a la funcion para generar el listado pasandole lineas 
                    $this->genera_pdf($lineas, $pdf_doc, TRUE, $pap_sum, $iglu_sum);
                }
            }
        }
        $pdf_doc->show();
    }

    private function pdf_filtro_ayunts($ayuntamiento='', $empresa='') {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        $pdf_doc = new fs_pdf('a4', 'portrait', 'Courier');
        $pdf_doc->pdf->addInfo('Title', 'Recogidas Ayunts  del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Subject', 'Recogidas Ayunts del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
        
        // Busco todos los ayuntamientos existentes -> Devuelve IDs
        $ayunts_all = $this->recogidas_model->search_ayunta($ayuntamiento);
        //consulta para saber cuandos materiales hay para esta empresa en estas fechas
        $empresas_all = $this->recogidas_model->search_empresa($empresa);
        //consulta para saber cuandos materiales hay
        $materiales = array(1, 2, 3);

        // Realizo bucle con cada ID de ayuntamiento encontrado
        foreach ($ayunts_all as $ayunt) {
            foreach ($empresas_all as $empre) {
                foreach ($materiales as $mat) {
                    $lineas = $this->recogidas_model->search($empre->entidad_nombre, $_POST['dfecha'], $_POST['hfecha'], $mat, $ayunt->entidad_id);
                    if ($lineas) {
                        $this->pdf_titulo = 'Ayunto. '. $this->recogidas_model->nombre_ayunta($ayunt->entidad_id) . ': ' .$empre->entidad_nombre . ' del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha'];
                         //Llamo a la funcion para generar el listado pasandole lineas 
                        $this->genera_pdf($lineas, $pdf_doc, TRUE);
                    }
                }
            }
        }
        $pdf_doc->show();
    }    
    
    /*******************************************************
     * 
     * Funcion que crea los pdf estandar
     * 
     *******************************************************
     *       */    
    private function genera_pdf(&$lineas, &$pdf_doc, $salidas = TRUE, $pap_sum = '', $iglu_sum = '') {

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
                        'titulo' => $this->pdf_titulo
                    )
            );
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 12,
                        'cols' => array(
                            'titulo' => array('justification' => 'center')
                        ),
                        'shaded' => 0,
                        'width' => 540,
                        'showLines' => 0,
                        'xOrientation' => 'center'
                    )
            );
            $pdf_doc->pdf->ezText("\n", 6);
            /*             * ***************************************************************************************************************************************
             * Creamos la tabla con las lineas del informe :
             * 
             * Fecha    ...
             * ********************************************************************************************************************************************* */
            if ($salidas)
                $header = array(
                    'fecha' => 'Fecha',
                    'empresa' => 'Empresa',
                    'material' => 'Material',
                    'entrada' => 'Entrada',
                    'salida' => 'Salida',
                    'tipo' => 'Tipo',
                    'matricula' => 'Matricula',
                    'ayuntamiento' => 'Ayuntamiento',
                    'notas' => 'Nota'
                );
            else
                $header = array(
                    'fecha' => 'Fecha',
                    'empresa' => 'Empresa',
                    'material' => 'Material',
                    'entrada' => 'Entrada',
                    'tipo' => 'Tipo',
                    'matricula' => 'Matricula',
                    'ayuntamiento' => 'Ayuntamiento',
                    'notas' => 'Nota'
                );

            $pdf_doc->new_table();
            $pdf_doc->add_table_header($header);

            $saltos = 0;
            for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ( $linea_actual < $lineasrecogidas));) {
                $fila = array(
                    'fecha' => date("d/m/Y", strtotime($lineas[$linea_actual]->fecha)),
                    'empresa' => $lineas[$linea_actual]->entidad_nombre,
                    'material' => $lineas[$linea_actual]->nombre_material(),
                    'entrada' => $this->show_numero($lineas[$linea_actual]->entrada, 3),
                    'salida' => $this->show_numero($lineas[$linea_actual]->salida, 3),
                    'tipo' => $lineas[$linea_actual]->nombre_tipo(),
                    'matricula' => $lineas[$linea_actual]->matricula,
                    'ayuntamiento' => $lineas[$linea_actual]->nombre_ayunta(),
                    'notas' => $this->fix_html($lineas[$linea_actual]->notas)
                );

                $pdf_doc->add_table_row($fila);
                $totalentra = $totalentra + $lineas[$linea_actual]->entrada;
                $totalsal = $totalsal + $lineas[$linea_actual]->salida;
                $saltos++;
                $linea_actual++;
            }
            $pdf_doc->save_table(
                    array(
                        'fontSize' => 8,
                        'cols' => array(
                            'fecha' => array('justification' => 'center', 'width' => 60),
                            'empresa' => array('justification' => 'center', 'width' => 60),
                            'material' => array('justification' => 'center', 'width' => 55),
                            'entrada' => array('justification' => 'right', 'width' => 55),
                            'salida' => array('justification' => 'right', 'width' => 55),
                            'tipo' => array('justification' => 'center', 'width' => 85),
                            'matricula' => array('justification' => 'center', 'width' => 55),
                            'ayuntamiento' => array('justification' => 'center', 'width' => 70),
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

                $pap_totales = array(
                        'col1' => '<b>Puerta a Puerta:  </b>',
                        'totalentra' => $this->show_numero($pap_sum, 3),
                        'totalsal' => ' ',
                        'col2' => '  '
                    );
                $iglu_totales = array(
                        'col1' => '<b>Iglú:  </b>',
                        'totalentra' => $this->show_numero($iglu_sum, 3),
                        'totalsal' => ' ',
                        'col2' => '  '
                    );                 

                $pdf_doc->new_table();
                $pdf_doc->add_table_header($header_totales);
                if ($pap_sum!= '') $pdf_doc->add_table_row($pap_totales);
                if ($iglu_sum!= '') $pdf_doc->add_table_row($iglu_totales);
                $pdf_doc->save_table(
                        array(
                            'fontSize' => 8,
                            'cols' => array(
                                'col1' => array('justification' => 'right', 'width' => 170),
                                'totalentra' => array('justification' => 'right', 'width' => 60),
                                'totalsal' => array('justification' => 'right', 'width' => 55),
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

    /*******************************************************
     * 
     * 
     *******************************************************
     */
    private function pdf_recogidas_listado() {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        $pdf_doc = new fs_pdf('a4', 'landscape', 'Courier');
        $pdf_doc->pdf->addInfo('Title', 'Recogidas Ayunts del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Subject', 'Recogidas Ayunts del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
        
        //Aqui puedo hacer un bucle para buscar cada material en pagina completa
        $lineas = $this->recogidas_model->search('', $_POST['dfecha'], $_POST['hfecha'], '', '',$_POST['orden']);

        if ($lineas) {
            $lineasrecogidas = count($lineas);
            $linea_actual = 0;
            $lppag = 33; /// líneas por página
            $pagina = 1;
            $totalentra = 0;
            $totalsal = 0;            

            // Imprimimos las páginas necesarias
            while ($linea_actual < $lineasrecogidas) {
                /// salto de página
                if ($linea_actual > 0) {
                    $pdf_doc->pdf->ezNewPage();
                    $pdf_doc->pdf->ezText("\n", 10);
                    $pagina++;
                }
                /* ***************************************************************************************************************************************
                 * Creamos la cabecera de la página, en este caso para el modelo simple para plantilla
                 * 
                 * ********************************************************************************************************************************************* */
                $pdf_doc->new_table();
                $pdf_doc->add_table_header(
                        array(
                            'titulo' => 'Recogidas Ayuntamientos del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']
                        )
                );
                $pdf_doc->save_table(
                        array(
                            'fontSize' => 14,
                            'cols' => array(
                                'titulo' => array('justification' => 'center')
                            ),
                            'shaded' => 0,
                            'width' => 780,
                            'showLines' => 0,
                            'xOrientation' => 'center'
                        )
                );
                $pdf_doc->pdf->ezText("\n", 6);
                /* ****************************************************************************************************************************************
                 * Creamos la tabla con las lineas del informe :
                 * 
                 * Fecha    ...
                 * ********************************************************************************************************************************************* */
                $pdf_doc->new_table();
                $pdf_doc->add_table_header(
                        array(
                            'fecha' => 'Fecha',
                            'empresa' => 'Empresa',
                            'material' => 'Material',
                            'entrada' => 'Entrada',
                            'salida' => 'Salida',
                            'tipo' => 'Tipo',
                            'matricula' => 'Matricula',
                            'ayuntamiento' => 'Ayuntamiento',
                            'notas' => 'Nota'
                        )
                );

                $saltos = 0;
                for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ( $linea_actual < $lineasrecogidas));) {
                    $fila = array(
                        'fecha' => date("d/m/Y", strtotime($lineas[$linea_actual]->fecha)),
                        'empresa' => $lineas[$linea_actual]->entidad_nombre,
                        'material' => $lineas[$linea_actual]->nombre_material(),
                        'entrada' => $this->show_numero($lineas[$linea_actual]->entrada, 2),
                        'salida' => $this->show_numero($lineas[$linea_actual]->salida, 2),
                        'tipo' => $lineas[$linea_actual]->nombre_tipo(),
                        'matricula' => $lineas[$linea_actual]->matricula,
                        'ayuntamiento' => $lineas[$linea_actual]->nombre_ayunta(),
                        'notas' => $this->fix_html($lineas[$linea_actual]->notas)
                    );

                    $pdf_doc->add_table_row($fila);
                    $totalentra = $totalentra + $lineas[$linea_actual]->entrada;
                    $totalsal = $totalsal + $lineas[$linea_actual]->salida;                    
                    $saltos++;
                    $linea_actual++;
                }
                $pdf_doc->save_table(
                        array(
                            'fontSize' => 9,
                            'cols' => array(
                                'fecha' => array('justification' => 'center', 'width' => 70),
                                'empresa' => array('justification' => 'center', 'width' => 80),
                                'material' => array('justification' => 'center', 'width' => 70),
                                'entrada' => array('justification' => 'right', 'width' => 60),
                                'salida' => array('justification' => 'right', 'width' => 60),
                                'tipo' => array('justification' => 'center', 'width' => 100),
                                'matricula' => array('justification' => 'center', 'width' => 60),
                                'ayuntamiento' => array('justification' => 'center', 'width' => 80),
                                'notas' => array('justification' => 'left')
                            ),
                            'alignHeadings' => 'center',
                            'width' => 780,
                            'shaded' => 1,
                            'showLines' => 1,
                            'xOrientation' => 'center'
                        )
                );
                /****************************************************
                 * 
                 * Si es la ultima pagina creamos la tabla de totales
                 * 
                 */ 
                if ($linea_actual == count($lineas)) {
                    $pdf_doc->new_table();
                    $pdf_doc->add_table_header(
                            array(
                                'col1' => '<b>Totales ('.$linea_actual.'):  </b>',
                                'totalentra' => $this->show_numero($totalentra,2),
                                'totalsal' => $this->show_numero($totalsal,2),
                                'col2' => '         Diferencia: '.$this->show_numero($totalentra-$totalsal,2)
                            )
                    );
                    $pdf_doc->save_table(
                            array(
                                'fontSize' => 10,
                                'cols' => array(
                                    'col1' => array('justification' => 'right', 'width' => 220),
                                    'totalentra' => array('justification' => 'right', 'width' => 60),
                                    'totalsal' => array('justification' => 'right', 'width' => 60),
                                    'col2' => array('justification' => 'left')                                   
                                ),
                                'shaded' => 0,
                                'width' => 780,
                                'showLines' => 4,
                                'xOrientation' => 'center'
                            )
                    );
                }
                
                // Saltamos el cursor en la pagina final para crear footer 
                $pdf_doc->pdf->ezSetY(60);
                
                /* *****************************************************************************************************************************************                        
                 * 
                 * Creamos el bloque de FOOTER
                 * 
                 * ************************************************************************************ */
                $pdf_doc->new_table();
                $pdf_doc->add_table_row(
                        array(
                            'pagina' => 'Pag: '.$pagina . '/' . ceil(count($lineas) / $lppag),
                            'texto' => 'Email: '.$this->empresa->email.' | Tlf: '.$this->empresa->telefono
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
                            'width' => 780,
                            'showLines' => 4,
                            'xOrientation' => 'center'
                        )
                );
            }

            $pdf_doc->show();
        }
    }

    private function csv_recogidas_listado() {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        header("content-type:application/csv;charset=ISO-8859-1");
        header("Content-Disposition: attachment; filename=\"recogidas_ayunt.csv\"");
        echo "Fecha;Empresa;Material;Entrada;Salida;Tipo;Matricula;Ayuntamiento;Ecovidrio;Notas\n";
        
        $recogidas = $this->recogidas_model->search('', $_POST['dfecha'], $_POST['hfecha'], '', '', $_POST['orden']);        
        if($recogidas){
            foreach($recogidas as $recog)
            {
                if($recog->ecovidrio == '1') $ecovidrio_='SI'; else $ecovidrio_='NO';
                
                $linea = array(
                    'fecha' => $recog->fecha,
                    'empresa' => $recog->entidad_nombre,
                    'material' => utf8_decode($recog->nombre_material()),
                    'entrada' => str_replace('.', ',', $recog->entrada),
                    'salida' => str_replace('.', ',', $recog->salida),
                    'tipo' => utf8_decode($recog->nombre_tipo()),
                    'matricula' => $recog->matricula,
                    'ayuntamiento' => utf8_decode($recog->nombre_ayunta()),
                    'ecovidrio' =>  $ecovidrio_,
                    'notas' => utf8_decode($recog->notas)
                );                
            
                echo '"'.join('";"', $linea)."\"\n";
            }            
            
        }
    }
    
    private function fix_html($txt)
    {    
      $newt = str_replace('&lt;', '<', $txt);
      $newt = str_replace('&gt;', '>', $newt);
      $newt = str_replace('&quot;', '"', $newt);
      $newt = str_replace('&#39;', "'", $newt);
      return $newt;
    }    

    public function stats_materiales_month($material = '0') {
      $stats = array();
      $meses = array(
          1 => 'ene',
          2 => 'feb',
          3 => 'mar',
          4 => 'abr',
          5 => 'may',
          6 => 'jun',
          7 => 'jul',
          8 => 'ago',
          9 => 'sep',
          10 => 'oct',
          11 => 'nov',
          12 => 'dic'
      );
      
      if ($material == 1)
        $stats_carton = $this->stats_materiales_month_aux('1');     
      else if ($material == 2)
        $stats_chapa = $this->stats_materiales_month_aux('2');
      else if ($material == 3)
        $stats_vidrio = $this->stats_materiales_month_aux('3');           
      else {
        $stats_carton = $this->stats_materiales_month_aux('1');
        $stats_chapa = $this->stats_materiales_month_aux('2');
        $stats_vidrio = $this->stats_materiales_month_aux('3');           
      }
          
      
      foreach($stats_carton as $i => $value)
      {
          if ($value['salida'] != 0)
            $almacenado = $value['entrada'] - $value['salida'];
          else
            $almacenado = 0;
          
          $stats[$i] = array(
             'month' => $meses[ $value['month'] ],
             'total_carton_entrada' => round($value['entrada'], 2),
             'total_carton_salida' => round($value['salida'], 2),
             'almacenado_carton' => round($almacenado,2),
             'total_chapa_entrada' => 0,
             'total_chapa_salida' => 0,
             'almacenado_chapa' => 0,
             'total_vidrio_entrada' => 0,
             'total_vidrio_salida' => 0,
             'almacenado_vidrio' => 0
         );
      }
      
      foreach($stats_chapa as $i => $value)
      {
        $stats[$i]['month'] = $meses[ $value['month'] ];
        $stats[$i]['total_chapa_entrada'] = round($value['entrada'], 2);
        $stats[$i]['total_chapa_salida'] = round($value['salida'], 2);
        if ($value['salida'] != 0)
            $stats[$i]['almacenado_chapa'] = round($value['entrada'] - $value['salida'],2);
        else
            $stats[$i]['almacenado_chapa'] = 0;
      }
      
      foreach($stats_vidrio as $i => $value)
      {
        $stats[$i]['month'] = $meses[ $value['month'] ];
        $stats[$i]['total_vidrio_entrada'] = round($value['entrada'], 2);
        $stats[$i]['total_vidrio_salida'] = round($value['salida'], 2);
        if ($value['salida'] != 0)
            $stats[$i]['almacenado_vidrio'] = round($value['entrada'] - $value['salida'],2);
        else
            $stats[$i]['almacenado_vidrio'] = 0;
      }
      
      return $stats;
    }

    public function stats_materiales_month_aux($material = '1', $num = 11) {
      $table_name = 'recogida_diario';
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('01-m-Y').'-'.$num.' month'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 month', 'm') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('month' => $i, 'entrada' => 0, 'salida' => 0, );
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMMM')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%m')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(entrada) as entrada, sum(salida) as salida
         FROM ".$table_name." WHERE material_id=".$material." AND fecha >= ".$this->recogidas_model->var2str($desde)."
         AND fecha <= ".$this->recogidas_model->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY mes ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i] = array(
                'month' => $i,
                'entrada' => floatval($d['entrada']),
                'salida' => floatval($d['salida'])
            );
         }
      }
      return $stats;
    }

    private function date_range($first, $last, $step = '+1 day', $format = 'd-m-Y') {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $dates[] = date($format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

}
