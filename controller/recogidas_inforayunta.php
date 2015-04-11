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
        /// desactivamos la barra de botones
        $this->show_fs_toolbar = FALSE;
        $this->desde = Date('1-m-Y');
        $this->hasta = Date('d-m-Y', mktime(0, 0, 0, date("m") + 1, date("1") - 1, date("Y")));
        $this->recogidas_model = new recogida_diario();
    
        
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
