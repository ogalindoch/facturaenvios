<?php

namespace euroglas\facturaenvios;

class facturaenvios implements \euroglas\eurorest\restModuleInterface
{
    // Nombre oficial del modulo
    public function name() { return "facturaenvios"; }

    // Descripcion del modulo
    public function description() { return "Acceso a los facturaenvios en Mordor"; }

    // Regresa un arreglo con los permisos del modulo
    // (Si el modulo no define permisos, debe regresar un arreglo vacío)
    public function permisos()
    {
        $permisos = array();

        // $permisos['_test_'] = 'Permiso para pruebas';

        return $permisos;
    }

    // Regresa un arreglo con las rutas del modulo
    public function rutas()
    {
        // mayusculas y minusculas importan...
		// como debe ir?
		// se puede usar /?numEnvio=0
		// se puede usar /?pageSize=200
		$items['/facturaEnvios/']['GET'] = array(
			'name' => 'lista factura envios/',
			'callback' => 'listaFacturaEnvios',
			'token_required' => TRUE,
		);

		// se puede usar ?numEnvio=0
		// se puede usar ?pageSize=200
		$items['/facturaEnvios']['GET'] = array(
			'name' => 'lista factura envios',
			'callback' => 'listaFacturaEnvios',
			'token_required' => TRUE,
		);

		//
		//	Solicita un envio
		//
		$items['/facturaEnvios/[i:id]']['GET'] = array(
			'name' => 'un envio',
			'callback' => 'facturaEnvio',
			'token_required' => TRUE,
		);

        return $items;
    }
    
    /**
     * Define que secciones de configuracion requiere
     * 
     * @return array Lista de secciones requeridas
     */
    public function requiereConfig()
    {
        $secciones = array();

        $secciones[] = 'dbaccess';

        return $secciones;
    }

    private $config = array();

    /**
     * Carga UNA seccion de configuración
     * 
     * Esta función será llamada por cada seccion que indique "requiereConfig()"
     * 
     * @param string $sectionName Nombre de la sección de configuración
     * @param array $config Arreglo con la configuracion que corresponde a la seccion indicada
     * 
     */
    public function cargaConfig($sectionName, $config)
    {
        $this->config[$sectionName] = $config;
        if ($sectionName == 'dbaccess')
        {
            $this->connect_db();
        }
    }

    private function connect_db()
    {
        //static::$configFile = $this->config['dbaccess']['config'];        
        //if (static::$configFile != '')
		if ($this->config && $this->config['dbaccess'])
        {
            //$this->dbRing = new \euroglas\dbaccess\dbaccess(static::$configFile);
            $this->dbRing = new \euroglas\dbaccess\dbaccess($this->config['dbaccess']['config']);
            
            if( $this->dbRing->connect('TheRing') === false )
            {
                print($this->dbRing->getLastError());
            }
        }
    }

	function __construct()
    {        
        $this->DEBUG = isset($_REQUEST['debug']);
    }
    
    private function validaColumnas($string)
    {
        //return preg_replace('/[^a-zA-Z0-9,\s]/', '', $string); este filtro permite espacios
        //print("Validando [{$string}]\n");
        return( filter_var(
                    $string,
                    FILTER_VALIDATE_REGEXP,
                    array(
                        "options"=>array("regexp"=>'/^[\w\s.,\-]+$/')
                    )
                )
        );
    }

    public function listaFacturaEnvios() {
    	/*
		header('Access-Control-Allow-Origin: *');
		
		$envios = new ClassFacturaEnvios();
		*/

		$pagina = 1;
		$numEnvio = null;


		if(isset($_REQUEST['page']))
		{
			$pagina = $_REQUEST['page'];
		}
		if(isset($_REQUEST['pageSize']))
		{
			$this->facturaEnviosPorPagina = $_REQUEST['pageSize'];
		}
		if(isset($_REQUEST['filter']))
		{
			$filtro = urldecode( $_REQUEST['filter'] );
			$filtro = $this->validaColumnas($filtro);
			if($filtro===false)
			{
				http_response_code(400); // 400 Bad Request
			    header('content-type: application/json');
				die(json_encode(
					 array(
                        'codigo' => 400001,
                        'mensaje' => 'Invalid filter',
                        'descripcion' => 'El parametro filter contiene caracteres invalidos',
                        'detalles' => $_REQUEST['filter']
					))
				);
			}
			$this->filtro = explode(',',$filtro );
		}
		
		if(isset($_REQUEST['numEnvio']))
		{
			$numEnvio = $_REQUEST['numEnvio'];
		}

		$losEnvios = $this->getFacturaEnvios($pagina,$numEnvio);

		if( 	
			(isset( $_SERVER['HTTP_ACCEPT'] ) && stripos($_SERVER['HTTP_ACCEPT'], 'JSON')!==false)
			|| 
			(isset( $_REQUEST['format'] ) && stripos($_REQUEST['format'], 'JSON')!==false) 
		)
		{
			header('content-type: application/json');
			die( json_encode( $losEnvios ) );
		}
		else
		{
			// Formato no definido
			header('content-type: text/plain');
			//print_r($_REQUEST);
			die( print_r($losEnvios,true) );
		}
	}

	public function facturaEnvio($id=-1) {
		//global $loader, $twig;

		//$envios = new ClassFacturaEnvios();

		if(isset($_REQUEST['filter']))
		{
			$filtro = urldecode( $_REQUEST['filter'] );
			$filtro = $this->validaColumnas($filtro);
			if($filtro===false)
			{
				http_response_code(400); // 400 Bad Request
			    header('content-type: application/json');
				die(json_encode(
					array(
                        'codigo' => 400001,
                        'mensaje' => 'Invalid filter',
                        'descripcion' => 'El parametro filter contiene caracteres invalidos',
                        'detalles' => $_REQUEST['filter']
					))
				);
			}

			$this->filtro = explode(',',$filtro );
		}

		$elEnvio = $this->getFacturaEnvio($id);

		if( $elEnvio === false )
		{
			http_response_code(404); // 404 Not Found
		    header('content-type: application/json');
			die(json_encode(
				array(
                        'codigo' => 404001,
                        'mensaje' => 'El Envio no existe',
                        'descripcion' => 'El Envio solicitado no fue encontrado en el servidor',
                        'detalles' => $id
					))
			);
		}

		if( 
			(isset( $_SERVER['HTTP_ACCEPT'] ) && stripos($_SERVER['HTTP_ACCEPT'], 'json')!==false)
			|| (isset( $_REQUEST['format'] ) && stripos($_REQUEST['format'], 'json')!==false) 

		)
		{
			header('content-type: application/json');
		
			//print('<pre>');print_r($elPedido);print('</pre>');
			//$elTicket['NotaPedido'] = addcslashes($elPedido['NotaPedido'], "\r\n\t");

			//echo $twig->render('pedido.json', $elPedido ) ;
			//echo $twig->render('pedido.json', addcslashes( $elPedido, "\r\n" ) ) ;

			die( json_encode( $elEnvio ) );
		}
		else
		{
			// Formato no definido
			header('content-type: text/plain');
			//print_r($_REQUEST);
			die( print_r($elEnvio,true) );
		}
	}
	
	private function in_array_any($needles, $haystack) {
	   return !empty(array_intersect($needles, $haystack));
	}

	private function AddProperties($base, $newProperties)
	{	
		return array_merge((array)$base,(array)$newProperties);
	}

	private function getFacturaEnvios($page=null,$numEnvio=null,$numFacturaEnvio=null)
	{
		if($page==null) $page = 1;

		$rowOffset = ($page-1) * $this->facturaEnviosPorPagina;

		$status = [];

		if( count($this->filtro) > 0 )
		{
		    $verNuevos=$this->in_array_any(array('Nuevo','nuevo'), $this->filtro );
		    $verCancelados=$this->in_array_any(array('Cancelado','cancelado'), $this->filtro );
		    $verDescargados=$this->in_array_any( array('Descargado','descargado'), $this->filtro );

		    if($verNuevos)
			{
				$status[]='Nuevo';
			}
			if($verCancelados)
			{
				$status[]='Cancelado';
			}
			if($verDescargados)
			{
				$status[]='Descargado';
			}
		}

		if( ! $this->dbRing->queryPrepared('queryGetEnvios') )
		{
			$query = "
		 	SELECT *
		 	FROM factura_envio e
		 	WHERE
		 		(:idFacturaEnvio = '' or e.idFacturaEnvio REGEXP if (:idFacturaEnvio <> '',:idFacturaEnvio,'^$'))
		 		and
		 		(:idEnvio = '' or e.idEnvio REGEXP if (:idEnvio <> '',:idEnvio,'^$'))
		 		and
			 	(:Estatus = '' or e.EstatusFacturaEnvio REGEXP if (:Estatus <> '',:Estatus,'^$'))
			ORDER BY idEnvio DESC
			LIMIT {$this->facturaEnviosPorPagina}
			OFFSET :rowOffset
			"
			;

			$query = preg_replace("/\s+/" , " ", $query);
			$this->dbRing->prepare($query, 'queryGetEnvios');
		}

		$where = array(
			':idEnvio' =>  $numEnvio <> null?$numEnvio:'',
			':idFacturaEnvio' =>  $numFacturaEnvio <> null?$numFacturaEnvio:'',
			':rowOffset' => $rowOffset,
			':Estatus' => implode("|", $status)
		);

		$sth = $this->dbRing->execute_bind('queryGetEnvios','ssis',$where);
		
		if( $sth === false )
		{
			return $this->dbRing->getLastError();
		}

		$datosDeEnvios = array();

		$pedidos = new \euroglas\pedidos\pedidos();
		$pedidos->cargaConfig('dbaccess', $this->config['dbaccess']);
		
		while(	$datosDelEnvio = $sth->fetch(\PDO::FETCH_ASSOC) )
		{
			$datosDelPedido = $pedidos->getPedidos(null,$datosDelEnvio['idPedido']);

			$datosDelEnvio = $this->AddProperties($datosDelEnvio,$datosDelPedido[0]);
			//$datosDelEnvio['detalleDelPedido'] = $datosDelPedido;

			$datosDelEnvio['links'] = array('self'=>"/facturaEnvios/{$datosDelEnvio['idEnvio']}");

			if($this->DEBUG) {
				$datosDelEnvio['debug']['factura envios']['query'] = array(					
					'raw'=>$this->dbRing->rawQueryPrepared('queryGetEnvios'),
					'values' => $where,
				);
				$datosDelEnvio['debug']['factura envios']['parametros'] = array(
					'pedidosPorPagina' => $this->facturaEnviosPorPagina,
					'filtro' => $this->filtro,
				);
			}

			$datosDeEnvios[] = $datosDelEnvio;
		}

		//if( count($datosDePedidos) == 0 ) return false;
		
		return $datosDeEnvios;
	}

	private function getFacturaEnvio($idFacturaEnvio)
	{
		$status = [];

		if( count($this->filtro) > 0 )
		{
		    $verNuevos=$this->in_array_any(array('Nuevo','nuevo'), $this->filtro );
		    $verCancelados=$this->in_array_any(array('Cancelado','cancelado'), $this->filtro );
		    $verDescargados=$this->in_array_any( array('Descargado','descargado'), $this->filtro );

		    if($verNuevos)
			{
				$status[]='Nuevo';
			}
			if($verCancelados)
			{
				$status[]='Cancelado';
			}
			if($verDescargados)
			{
				$status[]='Descargado';
			}
		}
		
		$ArrayDatosDelTicket = $this->getFacturaEnvios(null,null,$idFacturaEnvio);
		
		if( count($ArrayDatosDelTicket) == 0 ) return false;
		
		$pedido = $ArrayDatosDelTicket[0];

		#foreach ($ArrayDatosDelTicket as $pedido)
		{
			if( ! $this->dbRing->queryPrepared('queryGetFacturasEnvioPartidas') )
			{
				$query = "
				SELECT
					pp.SKU,
					p.Descripcion,
					p.Linea,
					p.ClaveTipo,
					ep.Cantidad,
					pp.PrecioSinDescuento,
					pp.Descuento,
					pp.Precio,
					pp.Moneda,
					pp.Promos
				FROM
					factura_envio_partidas ep
				INNER JOIN factura_envio e ON e.idFacturaEnvio = ep.idFacturaEnvio
				INNER JOIN pedidopartidas pp ON pp.idPedido = e.idPedido
				AND pp.SKU = ep.SKU
				INNER JOIN variantes v ON (v.SKU = pp.SKU)
				INNER JOIN producto p ON (v.idProducto = p.idProducto)
				WHERE
					ep.idFacturaEnvio = :idFacturaEnvio
				";
				
				#AND 
				#e.idEnvio = :idEnvio				
				#AND e.idPedido = :idPedido
				#AND (:Estatus = '' or e.EstatusFacturaEnvio REGEXP if (:Estatus <> '',:Estatus,'^$'))

				// $query .= 'ORDER BY orden_de_surtido.idOrdenDeSurtido DESC ';

				$query = preg_replace("/\s+/" , " ", $query);
				$this->dbRing->prepare($query, 'queryGetFacturasEnvioPartidas');
			}

			$where = array(
				//':idEnvio' => $idEnvio,
				':idFacturaEnvio' => $idFacturaEnvio,
				//':idPedido' => $pedido['idPedido'],
				//':Estatus' => implode("|", $status)
			);
			
			$sth = $this->dbRing->execute('queryGetFacturasEnvioPartidas',$where);
			
			$partidasDelTicket = $sth->fetchAll(\PDO::FETCH_ASSOC);
			
			//$elPedido = $pedido['detalleDelPedido'][0];
			//$elPedido['idFacturaEnvio'] = $pedido['idFacturaEnvio'];
			$pedido['Partidas'] = $partidasDelTicket;

			if($this->DEBUG) {
				$pedido['debug']['factura envio partidas']['query'] = array(
					'raw'=>$this->dbRing->rawQueryPrepared('queryGetFacturasEnvioPartidas'),
					'values' => $where
				);
				$pedido['debug']['factura envio partidas']['parametros'] = array(
					'facturaEnviosPorPagina' => $this->facturaEnviosPorPagina,
					'filtro' => $this->filtro
				);
			}
		}
		
		return($pedido);
	}

	// Public Config parameters
	public $facturaEnviosPorPagina = 10;
	private $filtro = array();
	private $cols=array();
	private $origenDeSurtido = array();
	private $pedidosPorPaginaDefault = 10;
	private $dbRing;
	private $DEBUG;
}