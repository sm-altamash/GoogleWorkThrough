@extends('admin.layouts.master')
@section('title', 'Users')
@section('content')
    <section>
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">User Management /</span> Users</h4>
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="table" id="role-datatable">
                    <thead>
                        <tr>
                            <th class="not_include"></th>
                            <th>Sr.No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="not_include">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('drive.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" required>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        <ul>
            @foreach ($files as $file)
                <li>
                    <a href="{{ $file['webViewLink'] }}" target="_blank">{{ $file['name'] }}</a>
                    (ID: {{ $file['id'] }})
                </li>
            @endforeach
        </ul>

    </section>
@endsection
{{-- @section('script')
    <script>

    </script>
@endsection --}}
