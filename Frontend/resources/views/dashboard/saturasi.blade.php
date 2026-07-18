@extends('layouts.app')
@section('content')
    <h1>Saturasi Genre</h1>
    @foreach ($saturation['data'] as $row)
        <div>{{ $row['genreL1'] }} — {{ $row['status'] }}</div>
    @endforeach
@endsection
