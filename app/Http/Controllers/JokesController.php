<?php

namespace App\Http\Controllers;

use App\Joke;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class JokesController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $limit = $request->get('limit') ? $request->get('limit') : 5;

        if($search) {
            $jokes = Joke::orderBy('id', 'DESC')->where('joke', 'LIKE', "%$search%")->with(['User' => function($query) {
                $query->select('id', 'name');
            }])->select('id', 'joke', 'user_id')->paginate($limit);

            $jokes->appends([
                'search' => $search,
                'limit' => $limit
            ]);
        } else {
            $jokes = Joke::orderBy('id', 'DESC')->with(['User' => function($query) {
                $query->select('id', 'name');
            }])->select('id', 'joke', 'user_id')->paginate($limit);

            $jokes->appends(array(
                'limit' => $limit
            ));
        }

//        var_dump($jokes);
        if($jokes) {
            return Response::json(['status' => 'success', 'data' => $this->transformCollection($jokes)], 200);
        } else {
            return Response::json(['status' => 'error', 'message' => 'something went wrong!'], 404);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(! $request->joke or !$request->user_id) {
            return Response::json([
                'status' => 'error',
                'message' => 'Please Provider both body and user_id'
            ], 422);
        }
        $joke = Joke::create($request->all());
        return Response::json([
            'status' => 'success',
            'data' => $this->transform($joke)
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $joke = Joke::with(['User' => function($query) {
            $query->select('id', 'name');
        }])->find($id);

        $previous = Joke::where('id', '<', $joke->id)->max('id');

        $next = Joke::where('id', '>', $joke->id)->min('id');

        if($joke) {
            return Response::json([
                'status' => 'success',
                'data' => $this->transform($joke),
                'previous_joke_id' => $previous,
                'next_joke_id' => $next
            ],200);
        } else {
            return Response::json(['status' => 'error', 'message' => 'Joke does not exist'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!$request->joke && !$request->user_id) {
            return Response::json([
                'status' => 'error',
                'message' => 'Please provide both body and user_id'
            ], 422);
        }

        $joke = Joke::find($id);
        $joke->joke = $request->joke;
        $joke->user_id = $request->user_id;
        $joke->save();

        return Response::json([
            'status' => 'success',
            'message' => 'Joke updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = Joke::destroy($id);

        if($result) {
            return Response::json([
                'status' => 'success',
                'message' => 'Joke removed successfully.'
            ], 200);
        } else {
            return Response::json([
                'status' => 'error',
                'message' => 'Try to remove the joke that does not exist'
            ], 404);
        }
    }

    private function transformCollection($jokes) {
        $jokesArray = $jokes->toArray();
        return [
            'total' => $jokesArray['total'],
            'per_page' => intval($jokesArray['per_page']),
            'current_page' => $jokesArray['current_page'],
            'last_page' => $jokesArray['last_page'],
            'next_page_url' => $jokesArray['next_page_url'],
            'prev_page_url' => $jokesArray['prev_page_url'],
            'from' => $jokesArray['from'],
            'to' =>$jokesArray['to'],
            'data' => array_map([$this, 'transform'], $jokesArray['data'])
        ];
    }

    private function transform($joke) {
        return [
            'joke_id' => $joke['id'],
            'joke' => $joke['joke'],
            'submitted_by' => $joke['user']['name']
        ];
    }
}
