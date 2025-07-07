@extends('admin.layouts.master')
@section('title', 'Users')
@section('content')
    <section>
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Nadra /</span> Imports</h4>
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="table" id="role-datatable">
                    <thead>
                    <tr>
                        <th class="not_include"></th>
                        <th>No</th>
                        <th>Full Name</th>
                        <th>Father Name</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>CNIC</th>
                        <th>Family ID</th>
                        <th>Addresses</th>
                        <th>Province</th>
                        <th>District</th>
                        <th>Category</th>
                        <th class="not_include">Actions</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Add Modal -->
        <div class="modal fade" id="addNadraModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Upload New File</h3>
                            <p class="text-muted">Add new data in system.</p>
                        </div>
                        <form class="row" id="addNadraform" method="POST" action="{{ route('nadra.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="col-12 col-md-6 mb-3">
                                <label for="category" class="form-label">Category (Year) <span class="text-danger">*</span></label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    @for($year = 2020; $year <= date('Y'); $year++)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="excel_file" class="form-label">Choose Excel file (.xlsx/.xls) <span class="text-danger">*</span></label>
                                <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Upload File</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editNadraModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Edit Record</h3>
                            <p class="text-muted">Update existing record in system.</p>
                        </div>
                        <form class="row" id="editNadraform" method="POST">
                            @csrf
                            @method('POST')
                            <input type="hidden" id="edit_record_id" name="record_id">

                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_father_name" class="form-label">Father Name</label>
                                <input type="text" class="form-control" id="edit_father_name" name="father_name" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_gender" class="form-label">Gender</label>
                                <select class="form-control" id="edit_gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_cnic_number" class="form-label">CNIC Number</label>
                                <input type="text" class="form-control" id="edit_cnic_number" name="cnic_number" placeholder="12345-1234567-1" required>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_family_id" class="form-label">Family ID</label>
                                <input type="text" class="form-control" id="edit_family_id" name="family_id">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="edit_addresses" class="form-label">Addresses</label>
                                <textarea class="form-control" id="edit_addresses" name="addresses" rows="3"></textarea>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_province" class="form-label">Province</label>
                                <input type="text" class="form-control" id="edit_province" name="province">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="edit_district" class="form-label">District</label>
                                <input type="text" class="form-control" id="edit_district" name="district">
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Update Record</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Details Modal -->
        <div class="modal fade" id="fileDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Uploaded Files Details</h3>
                            <p class="text-muted">View all uploaded files and their data.</p>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Uploaded Files</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="filesList" class="list-group">
                                            <!-- Files will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">File Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="fileDataContainer">
                                            <div class="text-center text-muted">
                                                <i class="ti ti-file-search" style="font-size: 3rem;"></i>
                                                <p>Select a file to view its data</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script>
        // COMPLETE NADRA CRUD JavaScript Code
        $(document).ready(function() {
            var dataTable;

            // Initialize DataTable
            dataTable = $('#role-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ordering: true,
                order: [[1, 'asc']], // Order by ID column (index 1) ascending
                ajax: {
                    url: "{{ route('nadra.index') }}",
                    error: function(xhr, error, code) {
                        console.log('Ajax error:', xhr.responseText);
                        alert('Error loading data. Check console for details.');
                    }
                },
                columns: [
                    {
                        data: null,
                        defaultContent: '',
                        className: 'control',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'full_name',
                        name: 'full_name',
                        orderable: true
                    },
                    {
                        data: 'father_name',
                        name: 'father_name',
                        orderable: true
                    },
                    {
                        data: 'gender',
                        name: 'gender',
                        orderable: true
                    },
                    {
                        data: 'date_of_birth',
                        name: 'date_of_birth',
                        orderable: true
                    },
                    {
                        data: 'cnic_number',
                        name: 'cnic_number',
                        orderable: true
                    },
                    {
                        data: 'family_id',
                        name: 'family_id',
                        orderable: true
                    },
                    {
                        data: 'addresses',
                        name: 'addresses',
                        orderable: false
                    },
                    {
                        data: 'province',
                        name: 'province',
                        orderable: true
                    },
                    {
                        data: 'district',
                        name: 'district',
                        orderable: true
                    },
                    {
                        data: 'category',
                        name: 'category',
                        orderable: true
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [
                    {
                        className: 'control',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2,
                        targets: 0,
                        render: function(data, type, full, meta) {
                            return '';
                        }
                    },
                    {
                        "defaultContent": "-",
                        "targets": "_all"
                    }
                ],
                dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 5,
                lengthMenu: [5, 10, 25, 50, 75, 100],
                buttons: [
                    {
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle me-2 waves-effect waves-light',
                        text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [
                            {
                                extend: 'print',
                                text: '<i class="ti ti-printer me-1" ></i>Print',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                }
                            },
                            {
                                extend: 'csv',
                                text: '<i class="ti ti-file-text me-1" ></i>Csv',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="ti ti-file-description me-1"></i>Pdf',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="ti ti-copy me-1" ></i>Copy',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                }
                            }
                        ]
                    },
                    {
                        text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New</span>',
                        className: 'create-new btn btn-primary waves-effect waves-light',
                        attr: {
                            'data-bs-toggle': 'modal',
                            'data-bs-target': '#addNadraModal'
                        }
                    },
                    {
                        text: '<i class="ti ti-file-search me-sm-1"></i> <span class="d-none d-sm-inline-block">Show Details</span>',
                        className: 'show-details btn btn-info waves-effect waves-light',
                        attr: {
                            'data-bs-toggle': 'modal',
                            'data-bs-target': '#fileDetailsModal'
                        }
                    }
                ],
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                var data = row.data();
                                return 'Details of ' + data['full_name'];
                            }
                        }),
                        type: 'column',
                        renderer: function(api, rowIdx, columns) {
                            var data = $.map(columns, function(col, i) {
                                return col.title !== '' ?
                                    '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                    '<td>' + col.title + ':</td> ' +
                                    '<td>' + col.data + '</td>' +
                                    '</tr>' : '';
                            }).join('');
                            return data ? $('<table class="table"/><tbody />').append(data) : false;
                        }
                    }
                },
                stateSave: false,
                paging: true,
                pageLength: 5,
                deferRender: false
            });

            // Set table header title
            $('div.head-label').html('<h5 class="card-title mb-0">Nadra Records</h5>');

            // ===== FILE MANAGEMENT FUNCTIONS =====
            
            // Load files when show details modal is opened
            $('#fileDetailsModal').on('show.bs.modal', function() {
                loadUploadedFiles();
            });

            // Load uploaded files function
            function loadUploadedFiles() {
                $.ajax({
                    url: "{{ route('nadra.uploaded-files') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        var filesList = $('#filesList');
                        filesList.empty();

                        if (response.length > 0) {
                            response.forEach(function(file) {
                                var uploadedDate = new Date(file.uploaded_at).toLocaleDateString();
                                var fileItem = `
                                    <a href="#" class="list-group-item list-group-item-action file-item" data-file-id="${file.id}">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">${file.original_filename}</h6>
                                            <small class="text-muted">${uploadedDate}</small>
                                        </div>
                                        <p class="mb-1">Category: ${file.category}</p>
                                        <small class="text-muted">Records: ${file.total_records}</small>
                                    </a>
                                `;
                                filesList.append(fileItem);
                            });
                        } else {
                            filesList.html('<p class="text-muted text-center">No files uploaded yet.</p>');
                        }
                    },
                    error: function(jqXHR, exception) {
                        console.log('Files load error:', jqXHR.responseText);
                        $('#filesList').html('<p class="text-danger text-center">Error loading files.</p>');
                    }
                });
            }

            // Handle file item click
            $(document).on('click', '.file-item', function(e) {
                e.preventDefault();
                var fileId = $(this).data('file-id');

                $('.file-item').removeClass('active');
                $(this).addClass('active');

                loadFileData(fileId);
            });

            // Load file data function
            function loadFileData(fileId) {
                $('#fileDataContainer').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.ajax({
                    url: "{{ route('nadra.file-data', '') }}/" + fileId,
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        var fileInfo = response.file;
                        var records = response.records;
                        var uploadedDate = new Date(fileInfo.uploaded_at).toLocaleDateString();

                        var html = `
                            <div class="mb-3">
                                <h6>File Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Filename:</strong> ${fileInfo.original_filename}</p>
                                        <p><strong>Category:</strong> ${fileInfo.category}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Uploaded:</strong> ${uploadedDate}</p>
                                        <p><strong>Total Records:</strong> ${fileInfo.total_records}</p>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Full Name</th>
                                            <th>Father Name</th>
                                            <th>Gender</th>
                                            <th>DOB</th>
                                            <th>CNIC</th>
                                            <th>Province</th>
                                            <th>District</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        records.forEach(function(record) {
                            html += `
                                <tr>
                                    <td>${record.full_name || '-'}</td>
                                    <td>${record.father_name || '-'}</td>
                                    <td>${record.gender || '-'}</td>
                                    <td>${record.date_of_birth || '-'}</td>
                                    <td>${record.cnic_number || '-'}</td>
                                    <td>${record.province || '-'}</td>
                                    <td>${record.district || '-'}</td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        $('#fileDataContainer').html(html);
                    },
                    error: function(jqXHR, exception) {
                        console.log('File data load error:', jqXHR.responseText);
                        $('#fileDataContainer').html('<p class="text-danger text-center">Error loading file data.</p>');
                    }
                });
            }

            // ===== CRUD OPERATIONS =====

            // Handle Edit Button Click
            $(document).on('click', '.edit-btn', function(e) {
                e.preventDefault();
                var recordId = $(this).data('id');
                
                console.log('Edit button clicked for record ID:', recordId);

                // Show loading state
                var loadingHtml = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                $('#editNadraModal .modal-body').prepend('<div id="edit-loading">' + loadingHtml + '</div>');

                $.ajax({
                    url: "{{ route('nadra.edit', '') }}/" + recordId,
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Edit response:', response);
                        
                        // Remove loading state
                        $('#edit-loading').remove();
                        
                        const r = response.record;

                        // Populate form fields
                        $('#edit_record_id').val(r.id);
                        $('#edit_full_name').val(r.full_name || '');
                        $('#edit_father_name').val(r.father_name || '');
                        $('#edit_gender').val(r.gender || '');
                        $('#edit_date_of_birth').val(r.date_of_birth || '');
                        $('#edit_cnic_number').val(r.cnic_number || '');
                        $('#edit_family_id').val(r.family_id || '');
                        $('#edit_addresses').val(r.addresses || '');
                        $('#edit_province').val(r.province || '');
                        $('#edit_district').val(r.district || '');

                        // Clear any previous validation errors
                        $('.is-invalid').removeClass('is-invalid');
                        $('.invalid-feedback').remove();

                        // Show modal
                        $('#editNadraModal').modal('show');
                    },
                    error: function(jqXHR, exception) {
                        console.log('Edit load error:', jqXHR.responseText);
                        
                        // Remove loading state
                        $('#edit-loading').remove();
                        
                        if (typeof ShowToast === 'function') {
                            ShowToast('error', 'Error loading record data');
                        } else {
                            alert('Error loading record data');
                        }
                    }
                });
            });

            // Handle Update Form Submission
            $('#editNadraform').submit(function(e) {
                e.preventDefault();
                
                var recordId = $('#edit_record_id').val();
                var formData = new FormData(this);
                
                // Add CSRF token
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                
                console.log('Submitting update for record ID:', recordId);
                
                // Show loading state on submit button
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Updating...').prop('disabled', true);
                
                // Clear previous validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                $.ajax({
                    url: "{{ route('nadra.update', '') }}/" + recordId,
                    type: "POST", 
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Update response:', response);
                        
                        // Reset submit button
                        submitBtn.html(originalText).prop('disabled', false);
                        
                        if (response.success) {
                            // Close modal
                            $('#editNadraModal').modal('hide');
                            
                            // Show success message
                            if (typeof ShowToast === 'function') {
                                ShowToast('success', response.message || 'Record updated successfully');
                            } else {
                                alert(response.message || 'Record updated successfully');
                            }
                            
                            // Reload datatable
                            dataTable.ajax.reload(null, false);
                        } else {
                            // Handle server-side error
                            var errorMsg = response.message || 'Unknown error occurred';
                            if (typeof ShowToast === 'function') {
                                ShowToast('error', errorMsg);
                            } else {
                                alert('Error: ' + errorMsg);
                            }
                        }
                    },
                    error: function(jqXHR, exception) {
                        console.log('Update error:', jqXHR.responseText);
                        
                        // Reset submit button
                        submitBtn.html(originalText).prop('disabled', false);
                        
                        if (jqXHR.status === 422) {
                            // Validation errors
                            var errors = jqXHR.responseJSON.errors;
                            
                            // Display validation errors
                            $.each(errors, function(field, messages) {
                                var input = $('#edit_' + field);
                                if (input.length) {
                                    input.addClass('is-invalid');
                                    input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                                }
                            });
                            
                            if (typeof ShowToast === 'function') {
                                ShowToast('error', 'Please fix the validation errors');
                            }
                        } else {
                            // Other errors
                            var errorMsg = 'Error updating record';
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg = jqXHR.responseJSON.message;
                            }
                            
                            if (typeof ShowToast === 'function') {
                                ShowToast('error', errorMsg);
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }
                });
            });

            // Handle Delete Button Click
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                var recordId = $(this).data('id');
                var row = $(this).closest('tr');
                
                console.log('Delete button clicked for record ID:', recordId);
                
                // Confirm deletion
                if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                    
                    // Show loading state
                    $(this).html('<span class="spinner-border spinner-border-sm" role="status"></span>').prop('disabled', true);
                    
                    $.ajax({
                        url: "{{ route('nadra.destroy', '') }}/" + recordId,
                        type: "POST", // Using POST as per your routes
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('Delete response:', response);
                            
                            if (response.success) {
                                // Show success message
                                if (typeof ShowToast === 'function') {
                                    ShowToast('success', response.message || 'Record deleted successfully');
                                } else {
                                    alert(response.message || 'Record deleted successfully');
                                }
                                
                                // Reload datatable
                                dataTable.ajax.reload(null, false);
                            } else {
                                // Handle server-side error
                                var errorMsg = response.message || 'Unknown error occurred';
                                if (typeof ShowToast === 'function') {
                                    ShowToast('error', errorMsg);
                                } else {
                                    alert('Error: ' + errorMsg);
                                }
                                
                                // Reset button state
                                $(this).html('<i class="ti ti-trash"></i>').prop('disabled', false);
                            }
                        },
                        error: function(jqXHR, exception) {
                            console.log('Delete error:', jqXHR.responseText);
                            
                            var errorMsg = 'Error deleting record';
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg = jqXHR.responseJSON.message;
                            }
                            
                            if (typeof ShowToast === 'function') {
                                ShowToast('error', errorMsg);
                            } else {
                                alert(errorMsg);
                            }
                            
                            // Reset button state
                            $(this).html('<i class="ti ti-trash"></i>').prop('disabled', false);
                        }
                    });
                }
            });

            // ===== CNIC VALIDATION =====
            
            // Real-time CNIC validation
            $('#edit_cnic_number').on('blur', function() {
                var cnic = $(this).val();
                var recordId = $('#edit_record_id').val();
                
                if (cnic && cnic.length > 0) {
                    validateCnic(cnic, recordId, $(this));
                }
            });

            
            
            // Clear form when modal is closed
            $('#editNadraModal').on('hidden.bs.modal', function() {
                $('#editNadraform')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.is-valid').removeClass('is-valid');
                $('.invalid-feedback').remove();
            });

            // ===== UTILITY FUNCTIONS =====
            
            // Global function to refresh datatable (can be called from outside)
            window.refreshNadraTable = function() {
                if (dataTable) {
                    dataTable.ajax.reload(null, false);
                }
            };

            // Format CNIC input (auto-add dashes)
            $(document).on('input', '#edit_cnic_number', function() {
                var value = $(this).val().replace(/[^\d]/g, '');
                var formattedValue = '';
                
                if (value.length > 0) {
                    formattedValue = value.substring(0, 5);
                    if (value.length > 5) {
                        formattedValue += '-' + value.substring(5, 12);
                        if (value.length > 12) {
                            formattedValue += '-' + value.substring(12, 13);
                        }
                    }
                }
                
                $(this).val(formattedValue);
            });

            // Log successful initialization
            console.log('NADRA CRUD JavaScript initialized successfully');
        });
    </script>
@endsection