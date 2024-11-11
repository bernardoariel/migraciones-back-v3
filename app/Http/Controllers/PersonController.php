<?php

namespace App\Http\Controllers;
use App\Models\Person;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class PersonController extends Controller
{
    public function gePersonas(){
        return response()->json(Person::all(),200);
    }

    public function getPersonaById($id){

        $personas = Person::find($id);

        if(is_null($personas)){
            return response()->json(['Mensaje'=>'Registro no encontrado'],404);
        }

        return response()->json($personas::find($id),200);
    }

    public function getPersonaByDocumento(Request $request){

        $menor = Person::where('numero_de_documento', $request->nro_doc)->first();

        if(is_null($menor)){
            return response()->json([],200);
        }

        return response()->json($menor,200);

    }

    public function getMenores(){

        $now = Carbon::now();
        $menores = Person::where('fecha_de_nacimiento', '!=', null)->get()->filter(function ($person) use ($now) {
            return $now->diffInYears(Carbon::parse($person->fecha_de_nacimiento)) < 21;
        });

        return response()->json($menores, 200);
    }

    public function getMenoresJoin(){

        $now = Carbon::now();
        $menores = Person::where('fecha_de_nacimiento', '!=', null)->get()->filter(function ($person) use ($now) {
            return $now->diffInYears(Carbon::parse($person->fecha_de_nacimiento)) < 21;
        });

        $menoresQuery = DB::table('persons') // cambiar 'minors' por 'persons'
            ->join('nationalities', 'persons.nationality_id', '=', 'nationalities.id')
            ->join('type_documents', 'persons.type_document_id', '=', 'type_documents.id')
            ->join('issuer_documents', 'persons.issuer_document_id', '=', 'issuer_documents.id')
            ->join('sexes', 'persons.sex_id', '=', 'sexes.id')
            ->select('persons.*', 'nationalities.nombre as nacionalidad',
            'type_documents.descripcion as tipo_documento',
             'issuer_documents.descripcion as emisor_documento',
              'sexes.descripcion as sexo')
            ->whereIn('persons.id', $menores->pluck('id')) // cambiar 'minors.id' por 'persons.id'
            ->orderBy('updated_at', 'desc')
            ->get();

        return $menoresQuery;
    }

    public function getPersonasAcompaneantesJoin(){

        $personasQuery = DB::table('persons')
            ->join('type_documents', 'persons.type_document_id', '=', 'type_documents.id')
            ->select('persons.id','persons.apellido', 'persons.segundo_apellido', 'persons.nombre',
            'persons.otros_nombres', 'persons.type_document_id',
             'persons.numero_de_documento','persons.updated_at')
            ->where(function ($query) {
                $now = Carbon::now();
                $query->where('fecha_de_nacimiento', '!=', null)
                      ->where('fecha_de_nacimiento', '<=', $now->subYears(21)->format('Y-m-d'));
            })
            ->orWhereNull('fecha_de_nacimiento')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $personasQuery;

    }

    public function getPersonasJoin(){

        $personasQuery = DB::table('persons')
            ->join('type_documents', 'persons.type_document_id', '=', 'type_documents.id')
            ->select(
                'persons.id', 'persons.apellido', 'persons.segundo_apellido', 'persons.nombre',
                'persons.otros_nombres', 'persons.type_document_id', 'persons.numero_de_documento',
                'persons.nationality_id', 'persons.issuer_document_id', 'persons.fecha_de_nacimiento',
                'persons.sex_id', 'persons.domicilio', 'persons.created_at', 'persons.updated_at',
                'persons.authorizing_relatives_id','persons.accreditation_links_id'
            )

            ->where(function ($query) {
                $now = Carbon::now();
                $query->where('fecha_de_nacimiento', '!=', null)
                      ->where('fecha_de_nacimiento', '<=', $now->subYears(21)->format('Y-m-d'));
            })
            ->orWhereNull('fecha_de_nacimiento')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $personasQuery;

       /*  $now = Carbon::now();
        $personas = Person::where('fecha_de_nacimiento', '!=', null)->get()->filter(function ($person) use ($now) {
            return $now->diffInYears(Carbon::parse($person->fecha_de_nacimiento)) >= 21;
        });

        $personasQuery = DB::table('persons') // cambiar 'minors' por 'persons'
            ->join('nationalities', 'persons.nationality_id', '=', 'nationalities.id')
            ->join('type_documents', 'persons.type_document_id', '=', 'type_documents.id')
            ->join('issuer_documents', 'persons.issuer_document_id', '=', 'issuer_documents.id')
            ->join('sexes', 'persons.sex_id', '=', 'sexes.id')
            ->select('persons.*', 'nationalities.nombre as nacionalidad', 'type_documents.descripcion as tipo_documento', 'issuer_documents.descripcion as emisor_documento', 'sexes.descripcion as sexo')
            ->whereIn('persons.id', $personas->pluck('id')) // cambiar 'minors.id' por 'persons.id'
            ->orWhereNull('fecha_de_nacimiento')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $personasQuery; */
    }

    public function getPersonaId($id){

        $persona = Person::find($id);

        if(is_null($persona)){
            return response()->json(['Mensaje'=>'Registro no encontrado'],404);
        }

        return response()->json($persona::find($id),200);
    }

    public function agregarPersona(Request $request){

        $data = $request->all();

        // Verificar si ya existe un registro con el mismo número de documento
        $personaExistente = Person::where('numero_de_documento', $data['numero_de_documento'])->first();

        // Si se encuentra un registro existente, devolver una respuesta de error
        if ($personaExistente) {
            return response(['error' => 'Ya existe una persona con este número de documento'], 422);
        }


        // Verificar si se envió la fecha de nacimiento
       /*  if (array_key_exists('fecha_de_nacimiento', $data)) {
            // Convertir la fecha en formato ISO-8601 a un objeto Carbon
            $fecha = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $data['fecha_de_nacimiento']);

            // Actualizar el campo fecha con la fecha formateada
            $data['fecha_de_nacimiento'] = $fecha->format('Y-m-d');
        } */

        // Crear el nuevo registro
        $persona = Person::create($data);

        return response($persona, 200);
    }

    public function actualizarPersona(Request $request, $id){
        // print_r($request->all());
        $persona = Person::find($id);
        if(is_null($persona)){
           return response()->json(['Mensaje'=>'Registro no encontrado'],404);
       }
        $datos_actualizados = array();
        foreach ($request->all() as $campo => $valor) {
           if (!is_null($valor)) {
               $datos_actualizados[$campo] = $valor;
           }
       }

       $fecha_nacimiento = Carbon::parse($request->fechaNacimiento)->format('Y-m-d');
       $datos_actualizados['fechaNacimiento'] = $fecha_nacimiento;
       $persona->update($datos_actualizados);
        // Obtener una copia actualizada de $persona
       $persona_actualizada = Person::find($id);
        return response()->json(
           [
               'Mensaje'=>'Registro actualizado correctamente',
               'persona' => $persona_actualizada,
               'request' => $request->all(),
           ],
           200
       );
   }

    public function eliminarPersona($id){

        $persona = Person::find($id);

        if(is_null($persona)){
            return response()->json(['Mensaje'=>'Registro no encontrado'],404);
        }

        $persona->delete();

        return response()->json(['mensaje'=>'registro eliminado'],200);
    }


}
