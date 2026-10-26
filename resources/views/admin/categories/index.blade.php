@extends('layouts.admin')
@section('title', 'Thể loại')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <table class="table align-middle small">
                <thead><tr><th>Tên thể loại</th><th>Slug</th><th class="text-center">Số sách</th><th class="text-center">Thứ tự</th><th></th></tr></thead>
                <tbody>
                @foreach($categories as $c)
                    @include('admin.categories._row', ['c' => $c, 'level' => 0])
                    @foreach($c->children as $child)
                        @include('admin.categories._row', ['c' => $child, 'level' => 1])
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-lg-4">
        <form method="post" action="{{ route('admin.categories.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h6 fw-bold">Thêm thể loại</h2>
            @include('admin.categories._fields', ['cat' => null])
            <button class="btn btn-primary w-100">Thêm</button>
        </div></form>
    </div>
</div>
@endsection
