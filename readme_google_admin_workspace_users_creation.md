Use Cases & Applications
1. Educational Institution Scenarios

Student Enrollment: Automatically create email when student registers
Staff Onboarding: HR system triggers email creation for new employees
Bulk Operations: Create emails for entire semester's new students
Department Management: Organize users by organizational units

2. Corporate Environments

Employee Onboarding: Integration with HR systems
Contractor Management: Temporary accounts with specific permissions
Department Restructuring: Batch operations for organizational changes
Compliance Reporting: Audit trail for account creation

3. Integration Patterns

API-First Design: Can be consumed by frontend applications
Webhook Integration: Real-time synchronization with external systems
Batch Processing: Efficient handling of bulk operations
Error Recovery: Retry mechanisms for failed operations











# Complete Google Workspace Email System Implementation Guide

## 1. 🚀 Initial Setup & Configuration

### Step 1: Google Cloud Console Setup

```bash
# 1. Create Google Cloud Project
# 2. Enable Admin SDK API
# 3. Create Service Account
# 4. Download JSON key file
```

### Step 2: Laravel Environment Configuration

```php
// config/google.php
<?php
return [
    'domain' => env('GOOGLE_DOMAIN', 'yourinstitution.edu'),
    'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', storage_path('app/google-service-account.json')),
    'admin_email' => env('GOOGLE_ADMIN_EMAIL), // Super admin email who has domain admin rights
];

// config/app.php - Add these
'first_module_api_key' => env('FIRST_MODULE_API_KEY'),
'first_module_url' => env('FIRST_MODULE_URL'),
'webhook_secret' => env('WEBHOOK_SECRET'),
```

### Step 3: Environment Variables (.env)

```env
# Google Workspace Configuration
GOOGLE_DOMAIN=leads.edu.pk
GOOGLE_SERVICE_ACCOUNT_PATH=/path/to/service-account.json
GOOGLE_ADMIN_EMAIL=admin@leads.edu.pk

# External System Integration
FIRST_MODULE_API_KEY=your_api_key_here
FIRST_MODULE_URL=https://existing-system.com
WEBHOOK_SECRET=your_secure_webhook_secret
```

## 2. 📊 Database Setup

### Migration for InstitutionalEmail Model

```php
// database/migrations/create_institutional_emails_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstitutionalEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('institutional_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('department')->nullable();
            $table->string('google_user_id')->unique();
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->string('password'); // Temporary password
            $table->json('google_response')->nullable(); // Store full Google API response
            $table->timestamp('email_created_at');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('email');
            $table->index('google_user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('institutional_emails');
    }
}
```

### InstitutionalEmail Model

```php
// app/Models/InstitutionalEmail.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionalEmail extends Model
{
    protected $fillable = [
        'user_id', 'username', 'email', 'first_name', 'last_name',
        'department', 'google_user_id', 'status', 'password',
        'google_response', 'email_created_at', 'last_synced_at', 'notes'
    ];

    protected $casts = [
        'google_response' => 'array',
        'email_created_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'password' // Hide password from JSON responses
    ];

    // Relationship with User model
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Check if email is active in Google
    public function isActiveInGoogle(): bool
    {
        return $this->status === 'active';
    }
}
```

## 3. 📝 Request Validation

### CreateEmailRequest Form Request

```php
// app/Http/Requests/CreateEmailRequest.php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add your authorization logic
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('institutional_emails', 'user_id')->ignore($this->id)
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/', // Only alphanumeric, dots, underscores, hyphens
                Rule::unique('institutional_emails', 'username')->ignore($this->id)
            ],
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'department' => 'nullable|string|max:100',
            'org_unit' => 'nullable|string|max:200'
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username can only contain letters, numbers, dots, underscores, and hyphens',
            'username.unique' => 'This username is already taken',
            'user_id.unique' => 'This user already has an institutional email',
        ];
    }

    // Custom validation for business rules
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if username is reserved
            $reservedUsernames = ['admin', 'root', 'administrator'];
            if (in_array(strtolower($this->username), $reservedUsernames)) {
                $validator->errors()->add('username', 'This username is reserved');
            }
        });
    }
}
```

## 4. 🛣️ API Routes Setup

```php
// routes/api.php
<?php
use App\Http\Controllers\Api\AdminManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/emails')->middleware(['auth:api'])->group(function () {
    // Direct email creation
    Route::post('/', [AdminManagementController::class, 'createInstitutionalEmail']);
    
    // Batch creation from existing system
    Route::post('/batch-from-first-module', [AdminManagementController::class, 'createEmailFromFirstModule']);
    
    // Get email status
    Route::get('/user/{userId}/status', [AdminManagementController::class, 'getEmailStatus']);
    
    // Update email status
    Route::patch('/user/{userId}/status', [AdminManagementController::class, 'updateEmailStatus']);
    
    // Sync with Google
    Route::post('/user/{userId}/sync', [AdminManagementController::class, 'syncWithGoogle']);
    
    // Bulk operations
    Route::post('/bulk-suspend', [AdminManagementController::class, 'bulkSuspend']);
    Route::post('/bulk-activate', [AdminManagementController::class, 'bulkActivate']);
});

// Webhook endpoint (no authentication required but signature validated)
Route::post('/webhooks/email-creation', [AdminManagementController::class, 'webhookCreateEmail']);
```

## 5. 🎯 Usage Scenarios & Examples

### Scenario 1: Student Registration System Integration

```php
// In your existing student registration controller
public function registerStudent(Request $request)
{
    DB::beginTransaction();
    try {
        // Create student record
        $student = Student::create($request->validated());
        
        // Auto-create institutional email
        $emailRequest = new CreateEmailRequest([
            'user_id' => $student->user_id,
            'username' => $student->student_id, // Use student ID as username
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'department' => $student->department,
            'org_unit' => '/Students/' . $student->faculty
        ]);
        
        $adminController = new AdminManagementController(new GoogleAdminService());
        $emailResult = $adminController->createInstitutionalEmail($emailRequest);
        
        if (!$emailResult->getData()->success) {
            throw new Exception('Failed to create institutional email');
        }
        
        DB::commit();
        
        // Send welcome email with login credentials
        Mail::to($student->personal_email)->send(new WelcomeStudentMail($student, $emailResult->getData()));
        
        return response()->json(['message' => 'Student registered successfully']);
        
    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

### Scenario 2: HR System Integration (Webhook)

```php
// External HR system sends webhook when new employee is hired
// POST /api/webhooks/email-creation

{
    "user_id": 123,
    "username": "john.doe",
    "first_name": "John",
    "last_name": "Doe",
    "department": "Engineering"
}

// Your webhook will automatically create Google Workspace account
```

### Scenario 3: Batch Email Creation for New Semester

```php
// Admin panel or command to create emails for all new students
public function createEmailsForNewSemester()
{
    $newStudents = Student::where('semester', '2024-fall')
                         ->whereDoesntHave('institutionalEmail')
                         ->pluck('user_id')
                         ->toArray();
    
    $response = Http::post('/api/admin/emails/batch-from-first-module', [
        'user_ids' => $newStudents
    ]);
    
    $result = $response->json();
    
    // Send summary report to admin
    Mail::to('admin@leads.edu.pk')->send(new BatchEmailCreationReport($result));
}
```

## 6. 🖥️ Frontend Integration Examples

### Vue.js Component for Email Management

```javascript
// EmailManagement.vue
<template>
  <div class="email-management">
    <h2>Institutional Email Management</h2>
    
    <!-- Single Email Creation Form -->
    <form @submit.prevent="createSingleEmail">
      <input v-model="form.username" placeholder="Username" required>
      <input v-model="form.first_name" placeholder="First Name" required>
      <input v-model="form.last_name" placeholder="Last Name" required>
      <select v-model="form.department">
        <option value="Engineering">Engineering</option>
        <option value="Business">Business</option>
        <option value="Arts">Arts</option>
      </select>
      <button type="submit">Create Email</button>
    </form>
    
    <!-- Batch Creation -->
    <div class="batch-creation">
      <h3>Batch Email Creation</h3>
      <input type="file" @change="uploadUserList" accept=".csv">
      <button @click="createBatchEmails">Create Batch Emails</button>
    </div>
    
    <!-- Email Status List -->
    <div class="email-list">
      <table>
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="email in emails" :key="email.id">
            <td>{{ email.username }}</td>
            <td>{{ email.email }}</td>
            <td>{{ email.status }}</td>
            <td>
              <button @click="syncEmail(email.user_id)">Sync</button>
              <button @click="toggleStatus(email.user_id)">
                {{ email.status === 'active' ? 'Suspend' : 'Activate' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      form: {
        username: '',
        first_name: '',
        last_name: '',
        department: '',
        user_id: null
      },
      emails: [],
      selectedUsers: []
    }
  },
  
  methods: {
    async createSingleEmail() {
      try {
        const response = await axios.post('/api/admin/emails', this.form);
        if (response.data.success) {
          this.$toast.success('Email created successfully');
          this.loadEmails();
          this.resetForm();
        }
      } catch (error) {
        this.$toast.error(error.response.data.message);
      }
    },
    
    async createBatchEmails() {
      try {
        const response = await axios.post('/api/admin/emails/batch-from-first-module', {
          user_ids: this.selectedUsers
        });
        
        const result = response.data;
        this.$toast.success(`Created ${result.summary.successful} emails, ${result.summary.failed} failed`);
        this.loadEmails();
      } catch (error) {
        this.$toast.error('Batch creation failed');
      }
    },
    
    async syncEmail(userId) {
      try {
        await axios.post(`/api/admin/emails/user/${userId}/sync`);
        this.$toast.success('Email synced with Google');
        this.loadEmails();
      } catch (error) {
        this.$toast.error('Sync failed');
      }
    },
    
    async loadEmails() {
      const response = await axios.get('/api/admin/emails');
      this.emails = response.data.data;
    }
  },
  
  mounted() {
    this.loadEmails();
  }
}
</script>
```

## 7. 📱 Mobile App Integration (React Native)

```javascript
// EmailService.js
class EmailService {
  constructor(apiUrl, token) {
    this.apiUrl = apiUrl;
    this.token = token;
  }
  
  async createEmail(userData) {
    const response = await fetch(`${this.apiUrl}/admin/emails`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`
      },
      body: JSON.stringify(userData)
    });
    
    return response.json();
  }
  
  async getEmailStatus(userId) {
    const response = await fetch(`${this.apiUrl}/admin/emails/user/${userId}/status`, {
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    
    return response.json();
  }
}

// Usage in React Native component
const EmailManagementScreen = () => {
  const [emailService] = useState(new EmailService(API_URL, authToken));
  
  const createStudentEmail = async (studentData) => {
    const result = await emailService.createEmail({
      user_id: studentData.id,
      username: studentData.student_id,
      first_name: studentData.first_name,
      last_name: studentData.last_name,
      department: studentData.department
    });
    
    if (result.success) {
      Alert.alert('Success', 'Email created successfully');
    } else {
      Alert.alert('Error', result.message);
    }
  };
  
  return (
    // Your React Native UI here
  );
};
```

## 8. 🔧 Command Line Tools (Artisan Commands)

```php
// app/Console/Commands/CreateBulkEmails.php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAdminService;
use App\Models\Student;

class CreateBulkEmails extends Command
{
    protected $signature = 'emails:create-bulk {--semester=} {--department=}';
    protected $description = 'Create bulk institutional emails';

    public function handle()
    {
        $semester = $this->option('semester');
        $department = $this->option('department');
        
        $query = Student::whereDoesntHave('institutionalEmail');
        
        if ($semester) {
            $query->where('semester', $semester);
        }
        
        if ($department) {
            $query->where('department', $department);
        }
        
        $students = $query->get();
        
        $this->info("Found {$students->count()} students without institutional emails");
        
        if (!$this->confirm('Do you want to proceed?')) {
            return;
        }
        
        $googleService = new GoogleAdminService();
        $successCount = 0;
        
        foreach ($students as $student) {
            try {
                $userData = [
                    'email' => $student->student_id . '@leads.edu.pk',
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'password' => $googleService->generateTemporaryPassword()
                ];
                
                $result = $googleService->createUser($userData);
                
                if ($result['success']) {
                    // Store in database
                    InstitutionalEmail::create([
                        'user_id' => $student->user_id,
                        'username' => $student->student_id,
                        'email' => $userData['email'],
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'google_user_id' => $result['google_id'],
                        'status' => 'active',
                        'password' => $userData['password'],
                        'email_created_at' => now()
                    ]);
                    
                    $successCount++;
                    $this->info("✓ Created email for {$student->first_name} {$student->last_name}");
                } else {
                    $this->error("✗ Failed to create email for {$student->first_name} {$student->last_name}: {$result['error']}");
                }
                
            } catch (Exception $e) {
                $this->error("✗ Error processing {$student->first_name} {$student->last_name}: {$e->getMessage()}");
            }
        }
        
        $this->info("Completed! Created {$successCount} out of {$students->count()} emails");
    }
}
```

## 9. 🔒 Security & Best Practices

### Security Middleware

```php
// app/Http/Middleware/EmailManagementAuth.php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmailManagementAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // Check if user has permission to manage emails
        if (!$user || !$user->hasPermission('manage_institutional_emails')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Rate limiting for bulk operations
        if ($request->is('*/batch-*')) {
            // Allow only 5 batch operations per hour
            if (!$this->checkRateLimit($user, 'batch_operations', 5, 3600)) {
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }
        }
        
        return $next($request);
    }
    
    private function checkRateLimit($user, $operation, $limit, $seconds)
    {
        $key = "rate_limit:{$user->id}:{$operation}";
        $current = cache()->get($key, 0);
        
        if ($current >= $limit) {
            return false;
        }
        
        cache()->put($key, $current + 1, $seconds);
        return true;
    }
}
```

## 10. 📊 Monitoring & Analytics

### Email Creation Metrics

```php
// app/Services/EmailMetricsService.php
<?php
namespace App\Services;

use App\Models\InstitutionalEmail;
use Carbon\Carbon;

class EmailMetricsService
{
    public function getDashboardMetrics()
    {
        return [
            'total_emails' => InstitutionalEmail::count(),
            'active_emails' => InstitutionalEmail::where('status', 'active')->count(),
            'suspended_emails' => InstitutionalEmail::where('status', 'suspended')->count(),
            'emails_created_today' => InstitutionalEmail::whereDate('email_created_at', Carbon::today())->count(),
            'emails_created_this_month' => InstitutionalEmail::whereMonth('email_created_at', Carbon::now()->month)->count(),
            'department_breakdown' => $this->getDepartmentBreakdown(),
            'creation_trends' => $this->getCreationTrends()
        ];
    }
    
    private function getDepartmentBreakdown()
    {
        return InstitutionalEmail::selectRaw('department, COUNT(*) as count')
            ->groupBy('department')
            ->pluck('count', 'department')
            ->toArray();
    }
    
    private function getCreationTrends()
    {
        return InstitutionalEmail::selectRaw('DATE(email_created_at) as date, COUNT(*) as count')
            ->where('email_created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
```

This comprehensive guide shows you exactly where and how to implement this Google Workspace email management system in various scenarios, from educational institutions to corporate environments, with complete code examples and best practices.