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
        $this->articulos = $this->articulos->all();
        $this->busqueda = array(
            'contenido' => '',
            'tipo' => '',
            'orden' => 'fecha'
        );
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        
        //cargamos nuestro modelo vacio de tabla recogidas_diario
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
            
        }elseif (isset($_GET['id'])){
            //Editamos recogida de entrada o salida
            $this->template = "recogidas_empresas_edita";
            $this->page->title = "Edita Recogida Empresa: " . $_GET['id'];
            //$this->edita_recogida();    
            
        }elseif (isset($_GET['opcion'])){    
            //para añadir una nueva recogida de Entrada o Salida
            
            //***********************
            //nueva entrada
            //***********************
            if ($_GET['opcion'] == "nueva_entrada") {
                $this->page->title = "Nueva ENTRADA Recogida Proveedor";
                
                //Si ya existe proveedor entro aqui
                if (isset($_GET['codproveedor']) AND !empty($_GET['codproveedor'])) {
                    if (isset($_POST['articulo_id']) AND !empty($_POST['articulo_id'])) { /// editar
                        //Si se recibe articulo desde plantilla RECOGIDA_EMPRESA_ENTRADA
                        //Se Graba y se vuelve a listar todo
                        
                        $this->template = "recogidas_empresas";
                    }else{
                        $this->resultado = $this->proveedor->get($_GET['codproveedor']);
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
                    if (isset($_POST['articulo_id']) AND !empty($_POST['articulo_id'])) { 
                        //Si se recibe articulo desde plantilla de RECOGIDA_EMPRESA_SALIDA
                        //Se Graba y se vuelve a listar todo
                        
                        $this->template = "recogidas_empresas";
                    }else{
                        $this->resultado = $this->cliente->get($_GET['codcliente']);
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