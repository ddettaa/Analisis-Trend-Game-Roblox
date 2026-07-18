@extends('layouts.app')
@section('content')
    <h1>Ringkasan Genre</h1>
    @foreach ($ranking['ranking'] as $row)
        <div>{{ $row['genreL1'] }} — {{ $row['game_count'] }} game</div>
    @endforeach
@endsection
