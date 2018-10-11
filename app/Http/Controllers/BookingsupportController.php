<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Bookingsupport;
use App\User;
use Illuminate\Http\Request;

class BookingsupportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Bookingsupport  $bookingsupport
     * @return \Illuminate\Http\Response
     */
    public function show(Bookingsupport $bookingsupport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Bookingsupport  $bookingsupport
     * @return \Illuminate\Http\Response
     */
    public function edit(Bookingsupport $bookingsupport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Bookingsupport  $bookingsupport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Bookingsupport $bookingsupport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Bookingsupport  $bookingsupport
     * @return \Illuminate\Http\Response
     */
    public function destroy(Bookingsupport $bookingsupport)
    {
        //
    }


    public function bookingsupport(Request $request)
    {
        $bookingsupport = new Bookingsupport();

        $bookingsupport->roomId = $request->roomId;
        $bookingsupport->roomName = $request->roomName;
        $bookingsupport->bookingTime = $request->bookingTime;

        // explode for get Ip and MacAdress
        $array = explode( ' - ',$request->mac_adress);
        $bookingsupport->ip =  $array[0];
        $bookingsupport->mac_address =  $array[1];
        $bookingsupport->string_user = $request->random;

        $bookingsupport->save();
    }


}
