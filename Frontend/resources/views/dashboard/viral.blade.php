@extends('layouts.app')
@section('content')
    <h1>Game Viral Muda</h1>
    @foreach ($viral['data'] as $row)
        <div>{{ $row['name'] }} — {{ $row['playing_per_hari'] }}/hari</div>
    @endforeach
@endsection
