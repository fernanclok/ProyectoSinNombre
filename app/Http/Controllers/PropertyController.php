<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Contract;
use Illuminate\Http\Request;
use App\Models\Property;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Models\Appointment;
use App\Models\Rental_application;

class PropertyController extends Controller
{
    public function getProperties()
    {
        $properties = Property::join('zones', 'zones.id', '=', 'properties.zone_id')
            ->select('properties.*', 'zones.name as zone_name')
            ->where('availability', 'Available')
            ->with('comments')
            ->get()
            ->map(function ($property) {
                $photos = $property->property_photos_path ? json_decode($property->property_photos_path, true) : [];
                $property->property_photos_path = is_array($photos)
                    ? array_map(fn($photo) => asset($photo), $photos)
                    : [];
                return $property;
            });

        return response()->json($properties);
    }

    public function featuredProperties()
    {
        $properties = Property::join('zones', 'zones.id', '=', 'properties.zone_id')
            ->select('properties.*', 'zones.name as zone_name')
            ->where('availability', 'Available')
            ->orderBy('rental_rate', 'asc')
            ->limit(3)
            ->get()
            ->map(function ($property) {
                $photos = $property->property_photos_path ? json_decode($property->property_photos_path, true) : [];
                $property->property_photos_path = is_array($photos)
                    ? array_map(fn($photo) => asset($photo), $photos)
                    : [];
                return $property;
            });

        return response()->json($properties);
    }

    public function getComments($id)
    {
        $property_id = Contract::where('tenant_user_id', $id)->where('status', 'Active')->select('property_id')->first();

        if(!$property_id) {
            return response()->json("No property found");
        }

        $comments = Comment::where('property_id', $property_id->property_id)
            ->join('users', 'users.id', '=', 'comments.user_id')
            ->orderBy('comments.created_at', 'desc')
            ->select('comments.*', 'users.first_name as first_name', 'users.last_name as last_name')
            ->get();

        return response()->json($comments);
    }

    public function createComment(Request $request)
    {
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->comment_rate = $request->rating;
        $comment->property_id = $request->property_id;
        $comment->user_id = $request->user_id;
        $comment->save();
        //
        $comments = Comment::where('property_id', $request->property_id)->get();
        $total = 0;

        foreach ($comments as $comment) {
            $total += $comment->comment_rate;
        }

        $property = Property::find($request->property_id);

        $property->rental_rate = $total / count($comments);

        $property->save();

        return response()->json($comments);
    }

    public function show($id)
    {
        $property = Property::findOrFail($id);
        return response()->json($property);
    }

    public function destroy($id)
    {
        $property = Property::findOrFail($id);
        $property->delete();

        return response()->json(['message' => 'Property deleted successfully']);
    }

    public function getPropertyDetails($id)
    {
        // Validar que el ID de la propiedad sea un entero y exista en la base de datos
        $property = Property::join('zones', 'zones.id', '=', 'properties.zone_id')
            ->select('properties.*', 'zones.name as zone_name')
            ->where('properties.id', $id)
            ->with('comments.user')
            ->firstOrFail();

        $photos = $property->property_photos_path ? json_decode($property->property_photos_path, true) : [];
        $property->property_photos_path = is_array($photos)
            ? array_map(fn($photo) => asset($photo), $photos)
            : [];

        // Obtener las citas relacionadas con la propiedad
        $appointments = Appointment::where('property_id', $id)
            ->with('user:id,first_name,last_name,email') // Incluir solo los campos necesarios del usuario
            ->get()
            ->map(function ($appointment) {
                return [
                    'requested_date' => $appointment->requested_date,
                    'appointment_status' => $appointment->status,
                    'user' => $appointment->user
                ];
            });

        // Agregar las citas al resultado de la propiedad
        $property->appointments = $appointments;

        return response()->json($property);
    }

    public function getFilteredProperties(Request $request)
    {
        $params = $request->all();

        $params['allowPets'] = $params['allowPets'] === 'true';
        $params['parking'] = $params['parking'] === 'true';

        $filteredParams = array_filter($params, function ($param) {
            return $param !== null && $param !== false && $param !== '';
        });

        $properties = $this->formatQuery($filteredParams);

        return response()->json($properties);
    }

    public function formatQuery($params)
    {
        $query = Property::query();

        if (isset($params['maxPrice'])) {
            if ($params['maxPrice'] === '+10000') {
                $query->where('property_price', '>=', 0)
                    ->where('availability', 'Available');
            } else {
                $query->where('property_price', '<=', $params['maxPrice'])
                    ->where('availability', 'Available');
            }
        }

        $query->join('zones', 'zones.id', '=', 'properties.zone_id')
            ->select('properties.*', 'zones.name as zone_name')
            ->with('comments');

        if (isset($params['selectedZone'])) {
            $query->where('zones.name', 'like', '%' . $params['selectedZone'] . '%');
        }

        if (isset($params['allowPets'])) {
            $query->where('accept_mascots', $params['allowPets']);
        }

        if (isset($params['parking'])) {
            $query->where('have_parking', $params['parking']);
        }

        if (isset($params['bedrooms'])) {
            $query->where('total_rooms', '>=', $params['bedrooms']);
        }

        if (isset($params['bathrooms'])) {
            $query->whereRaw('total_bathrooms + half_bathrooms >= ?', [$params['bathrooms']]);
        }

        if (isset($params['m2'])) {
            $query->where('total_m2', '>=', $params['m2']);
        }

        $properties = $query->get()
            ->map(function ($property) {
                $photos = $property->property_photos_path ? json_decode($property->property_photos_path, true) : [];
                $property->property_photos_path = is_array($photos)
                    ? array_map(fn($photo) => asset($photo), $photos)
                    : [];
                return $property;
            });

        return $properties;
    }


    public function get(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $properties = Property::where('owner_user_id', $request->user_id)->get()->map(function ($property) {
            $photos = $property->property_photos_path ? json_decode($property->property_photos_path, true) : [];
            $property->property_photos_path = is_array($photos)
                ? array_map(fn($photo) => asset($photo), $photos)
                : [];
            return $property;
        });

        return response()->json($properties);
    }

    // Insertar en la base de datos
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'general_features' => 'nullable|array',
            'services' => 'nullable|array',
            'exteriors' => 'nullable|array',
            'environmentals' => 'nullable|array',

            'street' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'availability' => 'required|string',
            'total_bathrooms' => 'required|integer',
            'total_rooms' => 'required|integer',
            'total_m2' => 'required|integer',
            'have_parking' => 'required|boolean',
            'accept_mascots' => 'required|boolean',
            'property_price' => 'required|numeric',
            'property_details' => 'required|string',
            'zone_id' => 'required|integer|exists:zones,id',

            'colony' => 'nullable|string|max:100',
            'half_bathrooms' => 'nullable|integer',
            'surface_built' => 'nullable|integer',
            'total_surface' => 'nullable|numeric|min:0',
            'antiquity' => 'nullable|integer',
            'maintenance' => 'nullable|numeric',
            'state_conservation' => 'nullable|string|max:50',
            'wineries' => 'nullable|integer',
            'closets' => 'nullable|integer',
            'levels' => 'nullable|integer',
            'property_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'parking' => 'nullable|integer',
        ]);

        $property = new Property();
        $property->property_code = 'PTY-' .
        $property->street = $validatedData['street'];
        $property->number = $validatedData['number'];
        $property->city = $validatedData['city'];
        $property->state = $validatedData['state'];
        $property->postal_code = $validatedData['postal_code'];
        $property->availability = $validatedData['availability'];
        $property->total_bathrooms = $validatedData['total_bathrooms'];
        $property->total_rooms = $validatedData['total_rooms'];
        $property->total_m2 = $validatedData['total_m2'];
        $property->have_parking = $validatedData['have_parking'];
        $property->accept_mascots = $validatedData['accept_mascots'];
        $property->property_price = $validatedData['property_price'];
        $property->property_details = $validatedData['property_details'];
        $property->zone_id = $validatedData['zone_id'];
        $property->owner_user_id = $request->user_id;

        $property->colony = $validatedData['colony'];
        $property->half_bathrooms = $validatedData['half_bathrooms'];
        $property->surface_built = $validatedData['surface_built'];
        $property->total_surface = $validatedData['total_surface']?? null;
        $property->antiquity = $validatedData['antiquity'];
        $property->maintenance = $validatedData['maintenance'];
        $property->state_conservation = $validatedData['state_conservation'];
        $property->wineries = $validatedData['wineries'];
        $property->closets = $validatedData['closets'];
        $property->levels = $validatedData['levels'];
        $property->parking = $validatedData['parking'];
      
        $property->general_features = json_encode($validatedData['generalFeatures'] ?? []);
        $property->services = json_encode($validatedData['services'] ?? []);
        $property->exteriors = json_encode($validatedData['exteriors'] ?? []);
        $property->environmentals = json_encode($validatedData['environmentals'] ?? []);      

        // Guardar las fotos de la propiedad
        if ($request->hasFile('property_photos')) {
            $photos = [];
            foreach ($request->file('property_photos') as $photo) {
                $originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $photo->getClientOriginalExtension();
                $timestamp = now()->format('YmdHis');
                $newName = "{$originalName}_{$timestamp}.{$extension}";
                $destinationPath = public_path('properties_photos');
                $photo->move($destinationPath, $newName);
                $photos[] = "properties_photos/{$newName}";
            }
            $property->property_photos_path = json_encode($photos);
        }

        $property->save();
        // Generar el código único de la propiedad
        $property->property_code = 'PTY-' . random_int(1000, 9999) . $property->id;
        $property->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'property' => $property,
        ]);
    }


    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'availability' => 'required|string',
            'total_bathrooms' => 'required|integer',
            'total_rooms' => 'required|integer',
            'total_m2' => 'required|integer',
            'have_parking' => 'required|boolean',
            'accept_mascots' => 'required|boolean',
            'property_price' => 'required|numeric',
            'property_details' => 'required|string',

            'colony' => 'nullable|string|max:100',
            'half_bathrooms' => 'nullable|integer',
            'surface_built' => 'nullable|integer',
            'total_surface' => 'nullable|integer',
            'antiquity' => 'nullable|integer',
            'maintenance' => 'nullable|numeric',
            'state_conservation' => 'nullable|string|max:50',
            'wineries' => 'nullable|integer',
            'closets' => 'nullable|integer',
            'levels' => 'nullable|integer',
            'parking' => 'nullable|integer',
        ]);

        // Convertir valores vacíos en null
        foreach ($validatedData as $key => $value) {
            if ($value === '') {
                $validatedData[$key] = null;
            }
        }

        // Manejar campos faltantes en la solicitud
        $allFields = [
            'street', 'number', 'city', 'state', 'postal_code', 'availability',
            'total_bathrooms', 'total_rooms', 'total_m2', 'have_parking',
            'accept_mascots', 'property_price', 'property_details', 'colony',
            'half_bathrooms', 'surface_built', 'total_surface', 'antiquity',
            'maintenance', 'state_conservation', 'wineries', 'closets', 'levels', 'parking',
        ];

        foreach ($allFields as $field) {
            if (!array_key_exists($field, $validatedData)) {
                $validatedData[$field] = null;
            }
        }

        $property = Property::findOrFail($id);
        $property->update($validatedData);

        return response()->json($property);
    }


    public function getAllApplications()
    {
        //$application = Rental_application::all();

        $application = DB::table('rental_applications')->get();

        $data = [
            'applications' => $application,
            'status' => 200
        ];

        return response()->json($data);
    }

    public function createApplication(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'property_id' => 'required',
            'tenant_user_id' => 'required',
            'application_date' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validacion de los datos',
                'error' => $validator->errors(),
                'status' => 200
            ];

            return response()->json($data, 400);
        }

        // $exists = Rental_application::where('property_id', $request->property_id)
        // ->where('tenant_user_id', $request->tenant_user_id)
        // ->exists();

        // if ($exists) {
        //     return response()->json(['message' => 'You have already applied to this property'], 409);
        // }

        // $application = Rental_application::create([
        //     'property_id' => $request->property_id,
        //     'tenant_user_id' => $request->tenant_user_id,
        //     'application_date' => $request->application_date,
        //     'status' => $request->status
        // ]);

        // Verificar si ya existe una aplicación con los mismos datos
        $exists = DB::table('rental_applications')
            ->where('property_id', $request->property_id)
            ->where('tenant_user_id', $request->tenant_user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You have already applied to this property'], 409);
        }

        // Insertar la nueva aplicación y obtener su ID
        $applicationId = DB::table('rental_applications')->insertGetId([
            'property_id' => $request->property_id,
            'tenant_user_id' => $request->tenant_user_id,
            'application_date' => $request->application_date,
            'status' => $request->status,
        ]);

        if (!$applicationId) {
            $data = [
                'message' => 'Error creating the application',
                'status' => 500
            ];

            return response()->json($data);
        }

        $data = [
            'message' => 'Application created succesfully',
            'application' => $applicationId,
            'status' => 201
        ];

        return response()->json($data);
    }
}
