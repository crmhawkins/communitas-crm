<?php

namespace App\Http\Livewire;

use App\Models\Alertas;
use App\Models\Anuncio;
use App\Models\Comunidad;
use App\Models\Incidencia;
use App\Models\Seccion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class IncidenciasComponent extends Component
{
    use WithFileUploads;
    use LivewireAlert;
    public $formularioCheck;
    public $secciones;
    public $seccion;
    public $anuncios;
    public $seccion_id;
    public $comunidad_id;
    public $titulo;
    public $descripcion;
    public $nombre;
    public $telefono;
    public $tipo;
    public $url;
    public $ruta_imagen;
    public $subseccionCheck;
    public $fecha;

    public function mount($seccion_id)
    {
        $this->inicializarComponente($seccion_id);
    }

    public function seleccionarSeccionVolver()
    {
        $this->emit('seleccionarSeccion', $this->seccion->seccion_padre_id);
    }
    private function inicializarComponente($id)
    {
        $this->formularioCheck = 0;
        $this->subseccionCheck = 0;
        $this->secciones = Seccion::all();
        $this->seccion_id = $id;
        $this->seccion = Seccion::find($this->seccion_id);
        $this->tipo = 1;
        if ($id != 0) {
            $this->comunidad_id = session('comunidad_id', Auth::user()->comunidad_id);
            $this->anuncios = Incidencia::where('comunidad_id', $this->seccion->comunidad_id)->get();
            $usuario= User::find(Auth::user()->id);
            $alertas = $usuario->alertas()->where('comunidad_id',$this->comunidad_id)->wherePivot('status', 0)->get();
            foreach($alertas as $alerta){
            $alertaId = $alerta->id;
            $usuario->alertas()->updateExistingPivot($alertaId, ['status' => 1]);
            }

        }
    }

    public function formularioCheck()
    {
        if ($this->subseccionCheck == 1) {
            $this->subseccionCheck = 0;
        }
        if ($this->formularioCheck == 0) {
            $this->formularioCheck = 1;
        } else {
            $this->formularioCheck = 0;
        }
    }
    public function subseccionCheck()
    {
        if ($this->formularioCheck == 1) {
            $this->formularioCheck = 0;
        }
        if ($this->subseccionCheck == 0) {
            $this->subseccionCheck = 1;
        } else {
            $this->subseccionCheck = 0;
        }
    }
    public function submit()
    {
        $this->fecha = date('Y-m-d');
        // Validación de datos

        $validatedData = $this->validate(
            [
                'titulo' => 'required',
                'comunidad_id' => 'required',
                'descripcion' => 'nullable',
                'telefono' => 'required',
                'nombre' => 'required',
                'ruta_imagen' => 'nullable',
                'fecha' => 'required',

                ],
                // Mensajes de error
                [
                'titulo.required' => 'required',
                'comunidad_id.required' => 'required',
                'telefono.required' => 'required',
                'nombre.required' => 'required',
                'fecha.required' => 'required',
                ]
                    );
                    if ($this->ruta_imagen != null) {
                        $name = $this->titulo . "-" . $this->fecha . '.' . $this->ruta_imagen->extension();

                        $this->ruta_imagen->storePubliclyAs('public', 'archivos/' . $this->secciones->firstWhere('id', $this->seccion_id)->nombre . '/' . $name);

            $validatedData['ruta_imagen'] = $name;
        }

        // Guardar datos validados
        $usuariosSave = Incidencia::create($validatedData);

         $alertaSave = Alertas::create([
            'admin_user_id' =>0,
            'user_id' => Auth::user()->id,
            'tipo' =>0,
            'datetime' => Carbon::now(),
            'titulo' =>$this->titulo ,
            'comunidad_id'=>$this->comunidad_id,
            'descripcion'=>$this->descripcion,
            'nombre'=>$this->nombre,
        ]);
        $user_ids = User::where('role', 1)->pluck('id');
        $users = User::where('role', 1)->get();
        $alertaSave->users()->attach($user_ids, ['status' => 0]);
        $comunidad = Comunidad::find($this->comunidad_id);
        foreach($users as $user){
            enviarMensajeWhatsapp('nueva_incidencia', $comunidad->nombre , $user->telefono ,'es');
        }

        // Alertas de guardado exitoso
        if ($usuariosSave) {
            $this->alert('success', '¡Publicación registrada correctamente!', [
                'position' => 'center',
                'timer' => 3000,
                'toast' => true,
                'showConfirmButton' => true,
                'onConfirmed' => 'confirmed',
                'confirmButtonText' => 'ok',
                'timerProgressBar' => true,
            ]);
        } else {
            $this->alert('error', '¡No se ha podido guardar la información del socio!', [
                'position' => 'center',
                'timer' => 3000,
                'toast' => false,
            ]);
        }

    }

    public function render()
    {
        return view('livewire.incidencias-component');
    }

    public function getListeners()
    {
        return [
            'submit',
            'confirmed',
            'alertaGuardar',
            'seleccionarSeccion',
            'refreshComponent' => '$refresh',

        ];
    }
    public function seleccionarSeccion($id)
    {
        $this->inicializarComponente($id);
    }

    public function confirmed()
    {
        $this->inicializarComponente($this->seccion_id);
    }
}
