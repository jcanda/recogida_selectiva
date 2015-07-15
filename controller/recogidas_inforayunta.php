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

    public $desde;
    public $hasta;
    public $recogidas_model;

    public function __construct() {
        parent::__construct(__CLASS__, 'Informes Ayuntamiento', 'recogida selectiva', FALSE, TRUE);
        /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
    }

    /**
     * esta función se ejecuta si el usuario ha hecho login,
     * a efectos prácticos, este es el constructor
     */
    protected function process() {
        $this->desde = Date('1-m-Y');
        $this->hasta = Date('d-m-Y', mktime(0, 0, 0, date("m") + 1, date("1") - 1, date("Y")));
        $this->recogidas_model = new recogida_diario();
        
        if (isset($_POST['listado'])) {
            if ($_POST['listado'] == 'recogidas_filtro') {
                if ($_POST['generar'] == 'pdf') {
                    $this->pdf_recogidas_filtro();
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
    
    private function pdf_recogidas_listado() {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        $pdf_doc = new fs_pdf('a4', 'landscape', 'Courier');
        $pdf_doc->pdf->addInfo('Title', 'Recogidas Ayuntamiento del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Subject', 'Recogidas Ayuntamiento del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha']);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
        
        //Aqui puedo hacer un bucle para buscar cada material en pagina completa
        $lineas = $this->recogidas_model->search('', $_POST['dfecha'], $_POST['hfecha'], 'todos', $_POST['orden']);

        if ($lineas) {
            $lineasrecogidas = count($lineas);
            $linea_actual = 0;
            $lppag = 33; /// líneas por página
            $pagina = 1;

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
                //añado lineas en coordenadas exactas
                $pdf_doc->pdf->ezText('Recogidas Ayuntamientos del ' . $_POST['dfecha'] . ' al ' . $_POST['hfecha'], 14, array('aleft' => 170));
                $pdf_doc->pdf->ezText("\n", 6);

                /* ****************************************************************************************************************************************
                 * Creamos la tabla con las lineas del certificado :
                 * 
                 * Fecha    LER  Codigo_Operacion   Descripcion    Obserbaciones Cantidad
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
                            'ecovidrio' => 'Ecovidrio',
                            'notas' => 'Nota'
                        )
                );

                $saltos = 0;
                for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ( $linea_actual < $lineasrecogidas));) {
                    $fila = array(
                        'fecha' => date("d/m/Y", strtotime($lineas[$linea_actual]->fecha)),
                        'empresa' => $lineas[$linea_actual]->nombre_empresa(),
                        'material' => $lineas[$linea_actual]->nombre_material(),
                        'entrada' => $this->show_numero($lineas[$linea_actual]->entrada, 2),
                        'cantidad' => $this->show_numero($lineas[$linea_actual]->salida, 2),
                        'tipo' => $lineas[$linea_actual]->nombre_tipo(),
                        'matricula' => $lineas[$linea_actual]->matricula,
                        'ayuntamiento' => $lineas[$linea_actual]->nombre_ayunta(),
                        'ecovidrio' => $lineas[$linea_actual]->ecovidrio,
                        'notas' => $this->fix_html($lineas[$linea_actual]->notas)
                    );

                    $pdf_doc->add_table_row($fila);
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
                                'ecovidrio' => array('justification' => 'center', 'width' => 60),
                                'notas' => array('justification' => 'left')
                            ),
                            'alignHeadings' => 'center',
                            'width' => 780,
                            'shaded' => 1,
                            'showLines' => 1,
                            'xOrientation' => 'center'
                        )
                );
                
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
        
        $recogidas = $this->recogidas_model->search('', $_POST['dfecha'], $_POST['hfecha'], 'todos', $_POST['orden']);        
        if($recogidas){
            foreach($recogidas as $recog)
            {
                if($recog->ecovidrio == '1') $ecovidrio_='SI'; else $ecovidrio_='NO';
                
                $linea = array(
                    'fecha' => $recog->fecha,
                    'empresa' => $recog->nombre_empresa(),
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
