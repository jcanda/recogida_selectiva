<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Recogidas Empresas
 *
 * @author Zapasoft
 */

require_model('articulo.php');
require_model('proveedor.php');
require_model('cliente.php');
require_model('pais.php');
require_model('recogida_empresa.php');

class recogidas_empresas extends fs_controller
{
   public $recogidas_model;
   public $cliente;
   public $proveedor;
   public $direcciones;
   public $articulos;
   public $pais;
   public $resultado;
   public $busqueda;
   public $offset;
   public $allow_delete;
   
   public function __construct() {
      parent::__construct(__CLASS__, 'Recogidas Empresas', 'recogida selectiva', FALSE, TRUE);
      /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
   }

   /**
    * esta función se ejecuta si el usuario ha hecho login,
    * a efectos prácticos, este es el constructor
    */
   protected function process() {
        $this->agente = FALSE;
        $this->proveedor = new proveedor();
        $this->cliente = new cliente();
        $this->pais = new pais();
        $this->articulos = new articulo();
        $this->busqueda = array(
            'desde' => '',
            'hasta' => '',
            'filtro_tipo' => '',
            'orden' => 'fecha'
        );
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        
        //cargamos nuestro modelo vacio de tabla recogidas_empresa
        $this->recogidas_model = new recogida_empresa();

        //Realizamos un anidado segun los GET que recibamos
            
        if (isset($_REQUEST['buscar_proveedor'])) {
            // Primero para buscar proveedor:
            /// desactivamos la plantilla HTML
            $this->template = FALSE;

            $json = array();
            foreach ($this->proveedor->search($_REQUEST['buscar_proveedor']) as $prove) {
                $json[] = array('value' => $prove->nombre, 'data' => $prove->codproveedor);
            }

            header('Content-Type: application/json');
            echo json_encode(array('query' => $_REQUEST['buscar_proveedor'], 'suggestions' => $json));
            
        } elseif (isset($_REQUEST['buscar_cliente'])) {
            // Segfundo para buscar cliente:
            /// desactivamos la plantilla HTML
            $this->template = FALSE;

            $json = array();
            foreach ($this->cliente->search($_REQUEST['buscar_cliente']) as $client) {
                $json[] = array('value' => $client->nombre, 'data' => $client->codcliente);
            }

            header('Content-Type: application/json');
            echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
 
        } elseif (isset($_REQUEST['buscar_articulo'])) {
            // Tercero para buscar articulo:
            /// desactivamos la plantilla HTML
            $this->template = FALSE;

            $json = array();
            foreach ($this->articulos->search($_REQUEST['buscar_articulo']) as $arti) {
                $json[] = array('value' => $arti->descripcion." (".$arti->equivalencia.")",'ler' => $arti->equivalencia, 'data' => $arti->referencia);
            }

            header('Content-Type: application/json');
            echo json_encode(array('query' => $_REQUEST['buscar_articulo'], 'suggestions' => $json));
            
        }elseif (isset($_GET['id'])){
            //Editamos recogida de entrada o salida
            $this->template = "recogidas_empresas_edita";
            $this->page->title = "Edita Recogida Empresa: " . $_GET['id'];
            $this->edita_recogida();    
            
        }elseif (isset($_GET['opcion'])){    
            //para añadir una nueva recogida de Entrada o Salida
            
            //***********************
            //nueva entrada
            //***********************
            if ($_GET['opcion'] == "nueva_entrada") {
                $this->page->title = "Nueva ENTRADA Recogida Proveedor";
                
                //Si ya existe proveedor entro aqui
                if (isset($_GET['codproveedor']) AND !empty($_GET['codproveedor'])) {
                    if (isset($_POST['entrada']) AND $_POST['entrada']>0) { /// editar
                        //Si se recibe cantidad de entrada desde plantilla RECOGIDA_EMPRESA_ENTRADA
                        //Se Graba y se vuelve a listar todo
                        $this->nueva_recogida();
                                            
                        $this->offset = 0;                
                        if (isset($_GET['offset']))
                            $this->offset = intval($_GET['offset']);
                        
                        $this->resultado = $this->recogidas_model->all($this->offset);
                        $this->template = "recogidas_empresas";
                    }else{
                        $this->resultado = $this->proveedor->get($_GET['codproveedor']);
                        $this->direcciones = $this->resultado->get_direcciones();
                        $this->template = "recogidas_empresas_entrada"; 
                    }
                }
                //NUEVO proveedor y pasamos ID para crear el nueva Recogida
                elseif (!isset($_GET['codproveedor'])) {
                    $proveedor_id = $this->nuevo_proveedor();
                    $proveedor = $this->proveedor->get($proveedor_id);
                    $this->proveedor = $proveedor;
                    $this->resultado = $proveedor;
                    $this->template = "recogidas_empresas_entrada";
                } else{
                    $this->new_error_msg('Proveedor no encontrado.');
                    
                    //Si no esta ni proveedor ni nuevo damos error y  listamos todo
                    $this->offset = 0;
                
                    if (isset($_GET['offset']))
                        $this->offset = intval($_GET['offset']); 
                
                    $this->resultado = $this->recogidas_model->all($this->offset);
                    $this->template = "recogidas_empresas";
                }
            //***********************    
            //nueva salida
            //***********************
            }elseif($_GET['opcion'] == "nueva_salida"){
                $this->page->title = "Nueva SALIDA Recogida Cliente";
                
                //Si ya existe cliente entro aqui
                if (isset($_GET['codcliente']) AND !empty($_GET['codcliente'])) {
                    if (isset($_POST['salida']) AND $_POST['salida']>0) { 
                        //Si se recibe cantidad de Salida desde plantilla de RECOGIDA_EMPRESA_SALIDA
                        //Se Graba y se vuelve a listar todo
                        $this->nueva_recogida();
                        
                        $this->offset = 0;                
                        if (isset($_GET['offset']))
                            $this->offset = intval($_GET['offset']);
                        
                        $this->resultado = $this->recogidas_model->all($this->offset);
                        $this->template = "recogidas_empresas";
                    }else{
                        $this->resultado = $this->cliente->get($_GET['codcliente']);
                        $this->direcciones = $this->resultado->get_direcciones();
                        $this->template = "recogidas_empresas_salida"; 
                    }
                }                
                //NUEVO cliente y pasamos ID para crear el nueva Recogida
                elseif (!isset($_GET['codcliente'])) {
                    $cliente_id = $this->nuevo_cliente();
                    $cliente = $this->cliente->get($cliente_id);
                    $this->cliente = $cliente;
                    $this->resultado = $cliente;
                    $this->template = "recogidas_empresas_salida";
                } else{
                    $this->new_error_msg('Cliente no encontrado.');
                    
                    //Si no esta ni cliente ni nuevo damos error y  listamos todo
                    $this->offset = 0;
                
                    if (isset($_GET['offset']))
                        $this->offset = intval($_GET['offset']); 
                
                    $this->resultado = $this->recogidas_model->all($this->offset);
                    $this->template = "recogidas_empresas";
                }
            }//fin salida cliente
            
        }elseif (isset($_GET['delete'])){    
            //PAra eliminar recogida
            $this->resultado = $this->recogidas_model->get($_GET['delete']);
            if ($this->resultado) {
                if ($this->resultado->delete()) {
                    $this->new_message('Recogida de empresa eliminada correctamente.');
                } else
                    $this->new_error_msg('Imposible eliminar la recogida a esta empresa.');
            } else
                $this->new_error_msg('Recogida no encontrada.');

                $this->offset = 0;
                
                if (isset($_GET['offset']))
                    $this->offset = intval($_GET['offset']); 
                
                $this->resultado = $this->recogidas_model->all($this->offset);            
                $this->template = "recogidas_empresas";
        }
        //Para BUSCAR entrada o salida O FILTRAR
        else if (isset($_POST['buscar'])){
            $this->busqueda['filtro_tipo'] = $_POST['filtro_tipo'];
            
            $this->resultado = $this->recogidas_model->search($_POST['buscar'], '','', $this->busqueda['filtro_tipo']);
            $this->template = "recogidas_empresas";        
        }else {
            //Si no entro en ningun otra opcion: listar todo
            $this->offset = 0;
            if (isset($_GET['offset']))
                $this->offset = intval($_GET['offset']);

            $this->resultado = $this->recogidas_model->all($this->offset);
            $this->template = "recogidas_empresas";
        }
    }

    public function nuevo_proveedor() {
        //----------------------------------------------
        // agrega un proveedor nuevo y retorna el id
        //----------------------------------------------

        if (isset($_POST['nombre'])) {
            $proveedor = new proveedor();
            $proveedor->codproveedor = $proveedor->get_new_codigo();
            $proveedor->nombre = $_POST['nombre'];
            $proveedor->nombrecomercial = $_POST['nombre'];
            $proveedor->cifnif = $_POST['cifnif'];
            $proveedor->telefono1 = $_POST['telefono1'];
            $proveedor->telefono2 = $_POST['telefono2'];
            $proveedor->codserie = $this->empresa->codserie;

            if ($proveedor->save()) {
                $dirproveedor = new direccion_proveedor();
                $dirproveedor->codproveedor = $proveedor->codproveedor;
                $dirproveedor->codpais = $_POST['pais'];
                $dirproveedor->provincia = $_POST['provincia'];
                $dirproveedor->ciudad = $_POST['ciudad'];
                $dirproveedor->codpostal = $_POST['codpostal'];
                $dirproveedor->direccion = $_POST['direccion'];
                $dirproveedor->descripcion = 'Principal';
                if ($dirproveedor->save()) {
                    $this->new_message('Proveedor agregado correctamente.');
                } else
                    $this->new_error_msg("¡Imposible guardar la dirección del Proveedor!");
            } else
                $this->new_error_msg('Error al agregar los datos del proveedor.');
        }

        return $proveedor->codproveedor;
    }    
    
    public function nuevo_cliente() {
        //----------------------------------------------
        // agrega un cliente nuevo y retorna el id
        //----------------------------------------------

        if (isset($_POST['nombre'])) {
            $cliente = new cliente();
            $cliente->codcliente = $cliente->get_new_codigo();
            $cliente->nombre = $_POST['nombre'];
            $cliente->nombrecomercial = $_POST['nombre'];
            $cliente->cifnif = $_POST['cifnif'];
            $cliente->telefono1 = $_POST['telefono1'];
            $cliente->telefono2 = $_POST['telefono2'];
            $cliente->codserie = $this->empresa->codserie;

            if ($cliente->save()) {
                $dircliente = new direccion_cliente();
                $dircliente->codcliente = $cliente->codcliente;
                $dircliente->codpais = $_POST['pais'];
                $dircliente->provincia = $_POST['provincia'];
                $dircliente->ciudad = $_POST['ciudad'];
                $dircliente->codpostal = $_POST['codpostal'];
                $dircliente->direccion = $_POST['direccion'];
                $dircliente->descripcion = 'Principal';
                if ($dircliente->save()) {
                    $this->new_message('Cliente agregado correctamente.');
                } else
                    $this->new_error_msg("¡Imposible guardar la dirección del Cliente!");
            } else
                $this->new_error_msg('Error al agregar los datos del Cliente.');
        }

        return $cliente->codcliente;
    }
    
   protected function nueva_recogida() {
        //----------------------------------------------
        // agrega una recogida nueva
        //----------------------------------------------
        //Si la fecha no se detalla se selecciona la de hoy
        if ($_POST['fecha'] == '') {
            $this->recogidas_model->fecha = date('d-m-Y');
        } else
            $this->recogidas_model->fecha = $_POST['fecha'];

        //Codigo empresa y nombre segun sea entrada o salida
        //Codigo y TIpo segun sea entrada o salida
        if ($_GET['opcion'] == "nueva_entrada") {
            $this->recogidas_model->empresa_id = $_GET['codproveedor'];
            $this->recogidas_model->entrada_empresa = floatval($_POST['entrada']);
            $this->recogidas_model->tipo_id = 1;
        } else {
            $this->recogidas_model->empresa_id = $_GET['codcliente'];
            $this->recogidas_model->salida_empresa = floatval($_POST['salida']);
            $this->recogidas_model->tipo_id = 2;
        }
        $this->recogidas_model->direccion_id = $_POST['direccion_id'];
        $this->recogidas_model->articulo_id = $_POST['articulo_id'];
        $this->recogidas_model->ler_ambiente = $_POST['ler_ambiente'];
        $this->recogidas_model->descrip_ambiente = $_POST['descrip_ambiente'];
        $this->recogidas_model->matricula = $_POST['matricula'];
        $this->recogidas_model->notas = $_POST['notas'];

        if ($this->recogidas_model->save()) {
            $this->new_message('Datos de la Recogida guardados correctamente.');
        } else {
            $this->new_error_msg('Imposible guardar los datos de la nueva Recogida.');
            return FALSE;
        }
    }

    protected function edita_recogida() {
        //----------------------------------------------
        // edita una entidad 
        //----------------------------------------------
        $this->resultado = $this->recogidas_model->get($_GET['id']);
        
        if ($this->resultado->tipo_id == 1){
            $proveedor_select = $this->proveedor->get($this->resultado->empresa_id);
            $this->direcciones = $proveedor_select->get_direcciones();
        }elseif($this->resultado->tipo_id == 2){
            $cliente_select = $this->cliente->get($this->resultado->empresa_id);
            $this->direcciones = $cliente_select->get_direcciones();
        }

        if ($this->resultado) {
            $this->agente = $this->user->get_agente();
        }

        if ($this->resultado AND ( $_POST['entrada'] > 0 OR $_POST['salida'] > 0)) {

            if (!empty($_POST['articulo_id']))
                $this->resultado->articulo_id = $_POST['articulo_id'];

            if ($_POST['fecha'] != '')
                $this->resultado->fecha = $_POST['fecha'];

            $this->resultado->direccion_id = $_POST['direccion_id'];
            $this->resultado->ler_ambiente = $_POST['ler_ambiente'];
            $this->resultado->descrip_ambiente = $_POST['descrip_ambiente'];
            $this->resultado->entrada_empresa = floatval($_POST['entrada']);
            $this->resultado->salida_empresa = floatval($_POST['salida']);
            $this->resultado->matricula = $_POST['matricula'];
            $this->resultado->notas = $_POST['notas'];

            if ($this->resultado->save())
                $this->new_message('Datos Recogida actualizados correctamente.');
            else
                $this->new_error_msg('Imposible actualizar los datos de la Recogida.');
        } elseif (!$this->resultado) 
            $this->new_error_msg('Datos recogida no encontrados.');
        
    }

    public function anterior_url()
   {
      $url = '';
      
      if($this->offset > 0)
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      
      if( count($this->resultado) == FS_ITEM_LIMIT )
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      }
      
      return $url;
   }   
}