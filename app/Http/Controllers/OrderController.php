<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class OrderController extends Controller
{
    public function getOrdenes(Request $request){

        $token = $request->header('Authorization');

        $personalAccessToken = PersonalAccessToken::where('token', $token)->first();

        $currentTime = time();
        $createdTime = strtotime($personalAccessToken->created_at);

        if ($personalAccessToken && $currentTime - $createdTime < 3600) {
            // Si el token existe, verificamos si ha expirado

                return response()->json(Order::all(), 200);

            } else {

                // El token ya ha expirado, debes devolver un mensaje de error
                return response()->json([
                    'error' => 'Invalid token'
                ], 401);

            }

    }

    public function getOrdenId($id){

        $ordenes = Order::find($id);

        if(is_null($ordenes)){
            return response()->json(['Mensaje'=>'Registro no encontrado'],404);
        }

        return response()->json($ordenes::find($id),200);
    }

    public function agregarOrden(Request $request){
        $validatedData = $request->validate([
            'numero_actuacion_notarial_cert_firma' => 'required|string|max:50',
            'instrumento' => 'required|string|max:1',
            'cualquier_pais' => 'required|string|max:1',
            'paises_desc' => 'nullable|string|max:191',
            'vigencia_hasta_mayoria_edad' => 'required|string|max:1',
            'fecha_del_instrumento' => 'required|date',
            'fecha_vigencia_desde' => 'required|date',
            'fecha_vigencia_hasta' => 'required|date',
            'notary_id' => 'required|integer',
            'minor_id' => 'required|integer',
            'autorizante1_id' => 'nullable|integer',
            'autorizante2_id' => 'nullable|integer',
            'serie_foja' => 'nullable|string|max:25',
            'tipo_foja' => 'nullable|string|max:25',
            'nro_foja' => 'nullable|string|max:50',
            'authorizing_relatives_id' => 'nullable|integer',
            'acompaneantes' => 'nullable|array',
        ]);
        
        // Confirma los datos validados
        // dd('Paso antes de crear la orden', $validatedData);
        /* Cargo una nueva solicitud con el menor y el notario */
        $orden = Order::create($validatedData);//aca lo creo

        $acompaneantes = $request->input('acompaneantes');
        // dd($acompaneantes);
        /* realizo la carga de los items */
        /* ============================= */
        //Escribano ->>
        $data = array(
            "order_id"=> Order::latest()->first()->id,
            "id_detalle"=> Order::latest()->first()->notary_id,
            "nombre_tabla"=> "notaries",
            "authorizing_relatives_id" => null,
            "accreditation_links_id" => null,
            "tipo"=> "escribano"
        );
        app(OrderItemController::class)->agregarOrdenItem($data);

        //Menor ->
        $data = array(
            "order_id"=> Order::latest()->first()->id,
            "id_detalle"=> Order::latest()->first()->minor_id,
            "nombre_tabla"=> "persons",
            "authorizing_relatives_id" => null,
            "accreditation_links_id" => null,
            "tipo" => 'menor'
        );
        app(OrderItemController::class)->agregarOrdenItem($data);

        //Autorizante 1 ->>
        if($request->autorizante1_id){
            $autorizante = Person::find($request->autorizante1_id);

            $data = array(
                "order_id"=> Order::latest()->first()->id,
                "id_detalle"=> $request->autorizante1_id,
                "nombre_tabla"=> "persons",
                "authorizing_relatives_id" => $autorizante->authorizing_relatives_id,
                "accreditation_links_id" => $autorizante->accreditation_links_id,
                "tipo" => 'autorizante'
            );

            app(OrderItemController::class)->agregarOrdenItem($data);
        }

        //Autorizante 2 ->>
        if($request->autorizante2_id){
            $autorizante2 = Person::find($request->autorizante2_id);

            $data = array(
                "order_id"=> Order::latest()->first()->id,
                "id_detalle"=> $request->autorizante2_id,
                "nombre_tabla"=> "persons",
                "authorizing_relatives_id" => $autorizante2->authorizing_relatives_id,
                "accreditation_links_id" => $autorizante2->accreditation_links_id,
                "tipo" => 'autorizante'
            );
            app(OrderItemController::class)->agregarOrdenItem($data);
        }
            foreach ($acompaneantes as $acompaneante) {

                $acompaneante = Person::find($acompaneante['id']);
                $data = array(
                    "order_id"=> Order::latest()->first()->id,
                    "id_detalle"=> $acompaneante['id'],
                    "nombre_tabla"=> "persons",
                    "authorizing_relatives_id" => $acompaneante->authorizing_relatives_id,
                    "accreditation_links_id" => $acompaneante->accreditation_links_id,
                    "tipo" => 'acompaneante'
                );
                app(OrderItemController::class)->agregarOrdenItem($data);
                // Hacer lo que necesites con cada acompañante aquí
            }
        $items = OrderItems::where('order_id',Order::latest()->first()->id)->get();

          foreach ($items as $item) {
            // Acceder a los atributos de cada elemento en la colección
            $id = $item->id;
            $order_id = $item->order_id;
            $id_detalle = $item->id_detalle;
            $nombre_tabla = $item->nombre_tabla;
            $created_at = $item->created_at;
            $updated_at = $item->updated_at;
            $authorizing_relatives_id = $item->authorizing_relatives_id;
            $accreditation_links_id = $item->accreditation_links_id;
            $tipo = $item->tipo;

            // Hacer lo que necesites con cada elemento aquí
            if($nombre_tabla=='persons'){
                $person = Person::where('id',  $id_detalle)->first();
                if ($person) {
                    $person->authorizing_relatives_id = null;
                    $person->accreditation_links_id = null;
                    $person->save();
                }
            }

        }

       return response($orden,200);

    }
    public function actualizarOrden(Request $request, $id){

        $orden = Order::find($id);

        if(is_null($orden)){

            return response()->json(['Mensaje'=>'Registro no encontrado'],404);

        }
        $orden->update($request->all());
        $orderItemController = app()->make(OrderItemController::class);
        $orderItemController->deleteOrderItems($id);

        /* realizo la carga de los items */
        /* ============================= */
        //Escribano ->>
        $data = array(
            "order_id"=> $orden->id,
            "id_detalle"=> $orden->notary_id,
            "nombre_tabla"=> "notaries",
        );
        app(OrderItemController::class)->agregarOrdenItem($data);

        //Menor ->
        $data = array(
            "order_id"=> $orden->id,
            "id_detalle"=> $orden->minor_id,
            "nombre_tabla"=> "minors",
        );
        app(OrderItemController::class)->agregarOrdenItem($data);

        //Acompañante 1 ->>
        if($request->acompaneante1_id){
            $data = array(
                "order_id"=> $orden->id,
                "id_detalle"=> $request->acompaneante1_id,
                "nombre_tabla"=> "authorizations",
            );
            // $controlador = new OrderItemController();
            app(OrderItemController::class)->agregarOrdenItem($data);
        }

        //Acompañante 2 ->>
        if($request->acompaneante2_id){
            $data = array(
                "order_id"=> $orden->id,
                "id_detalle"=> $request->acompaneante2_id,
                "nombre_tabla"=> "authorizations",
            );
            // $controlador = new OrderItemController();
            app(OrderItemController::class)->agregarOrdenItem($data);
        }

        foreach ($request->progenitores as $progenitor) {
            $data = [
            'order_id' => $orden->id,
            'id_detalle' => $progenitor,
            'nombre_tabla' => 'other_parents',
            ];
            app(OrderItemController::class)->agregarOrdenItem($data);
        }
        /* foreach ($request->acompaneantes as $acompaneante) {
            $data = [
            'order_id' => $orden->id,
            'id_detalle' => $acompaneante,
            'nombre_tabla' => 'persons',
            ];
            app(OrderItemController::class)->agregarOrdenItem($data);
        } */
       return response($orden,200);

    }

    public function eliminarOrden($id){

        $orden = Order::find($id);

        if(is_null($orden)){
            return response()->json(['Mensaje'=>'Registro no encontrado'],404);
        }

        $orden->delete();
        $orderItemController = app()->make(OrderItemController::class);
        $orderItemController->deleteOrderItems($id);
        return response()->json(['mensaje'=>'registro eliminado'],200);
    }

    public function actualizarOrdenAprobacion($data){

        $actualizacion = DB::table('orders')
        ->where('id', $data['id'])
        ->update(['aprobacion' => $data['aprobacion']]);
        if($actualizacion){

            return "ok";
        }
        return "es false";
    }

    public function getOrdenesTodos(Request $request){

        $ordenes = DB::table('orders')
            ->join('persons',   'orders.minor_id', '=', 'persons.id')
            ->join('notaries',   'orders.notary_id', '=', 'notaries.id')
            ->select('orders.*','persons.apellido as apellido','persons.segundo_apellido as segundo_apellido' ,
            'persons.nombre as nombre','persons.otros_nombres as otros_nombres',
            'persons.numero_de_documento as documento',
            'notaries.apellido as apellidoescribano','notaries.nombre as nombreescribano' )
            ->orderBy('id','DESC')
            ->get();
        return $ordenes;

    }

    public function duplicate($id)
    {
        $original = Order::find($id);

        $duplicate = new Order();
        $attributes = $original->getAttributes();
        unset($attributes['id']);
        $attributes['minor_id'] = 1;
        foreach ($attributes as $key => $value) {
            $duplicate->$key = $value;
        }
        $duplicate->save();
        $lastInsertId = $duplicate->id;  // Obtiene el último ID generado
        $orderItemController = app()->make(OrderItemController::class);
        $orderItemController->duplicate($id,$lastInsertId);
    }
}
