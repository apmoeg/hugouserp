# Frontend Documentation - HugousERP

## Overview

The HugousERP frontend is built using the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) with additional JavaScript components for specific features like the POS terminal. This document provides a comprehensive guide to the frontend architecture, patterns, and best practices.

## Table of Contents

1. [Technology Stack](#technology-stack)
2. [Frontend Architecture](#frontend-architecture)
3. [API Integration](#api-integration)
4. [State Management](#state-management)
5. [Component Patterns](#component-patterns)
6. [Form Handling](#form-handling)
7. [Error Handling](#error-handling)
8. [Loading States](#loading-states)
9. [Validation](#validation)
10. [Security Considerations](#security-considerations)
11. [Performance Optimization](#performance-optimization)
12. [Common Patterns](#common-patterns)

---

## Technology Stack

### Core Technologies

- **Laravel 12**: Backend framework providing routing, authentication, and business logic
- **Livewire 3**: Full-stack framework for building dynamic interfaces without leaving PHP
- **Alpine.js**: Lightweight JavaScript framework for adding interactivity (included with Livewire)
- **Tailwind CSS 4**: Utility-first CSS framework for styling
- **Vite**: Modern build tool for asset compilation
- **Axios**: HTTP client for API requests in JavaScript components

### Additional Libraries

- **Chart.js**: Data visualization for reports and dashboards
- **SweetAlert2**: Beautiful, responsive, customizable modals and alerts
- **Firebase**: Push notifications and real-time features (optional)
- **Lodash**: Utility library for common JavaScript operations

---

## Frontend Architecture

### Directory Structure

```
resources/
├── css/
│   └── app.css              # Main stylesheet with Tailwind imports
├── js/
│   ├── app.js               # Main JavaScript entry point
│   ├── bootstrap.js         # Axios setup and CSRF configuration
│   ├── pos.js               # POS terminal logic
│   └── firebase.js          # Firebase initialization
└── views/
    ├── layouts/
    │   ├── app.blade.php    # Main authenticated layout
    │   ├── guest.blade.php  # Guest layout for login/register
    │   ├── sidebar.blade.php # Navigation sidebar
    │   └── navbar.blade.php  # Top navigation bar
    ├── livewire/
    │   ├── [module]/        # Livewire component views by module
    │   │   ├── index.blade.php
    │   │   └── form.blade.php
    │   └── ...
    └── components/
        ├── loading-indicator.blade.php
        ├── success-alert.blade.php
        ├── error-alert.blade.php
        └── ...
```

### Application Entry Points

1. **resources/js/app.js**: Main JavaScript bundle
   - Imports Bootstrap.js for Axios setup
   - Imports POS terminal functionality
   - Sets up global utilities (Swal, Chart.js)
   - Initializes event listeners for notifications

2. **resources/css/app.css**: Main stylesheet
   - Imports Tailwind CSS directives
   - Custom CSS variables and utilities

---

## API Integration

### API Base URLs

All API endpoints are prefixed with `/api/v1/`:

```javascript
// Branch-scoped endpoints (used by POS)
/api/v1/branches/{branchId}/pos/checkout
/api/v1/branches/{branchId}/products/search

// Global endpoints
/api/v1/pos/checkout
/api/v1/products
/api/v1/customers
```

### API Response Format

All API responses follow a standardized format:

**Success Response:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**Paginated Response:**
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### HTTP Status Codes

- **200 OK**: Successful GET/PUT/PATCH request
- **201 Created**: Successful POST request (resource created)
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Authentication required or invalid
- **403 Forbidden**: User lacks permission
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server-side error

### Making API Requests

#### Using Axios (JavaScript Components)

```javascript
// GET request
try {
  const response = await window.axios.get('/api/v1/products/search', {
    params: { q: 'search term' },
    timeout: 10000 // 10 second timeout
  });
  
  const data = response.data;
  if (data.success && data.data) {
    // Handle success
  }
} catch (error) {
  if (error.response) {
    // Server responded with error
    const status = error.response.status;
    const message = error.response.data.message;
  } else if (error.request) {
    // No response received
  } else {
    // Request setup error
  }
}

// POST request
try {
  const response = await window.axios.post('/api/v1/branches/1/pos/checkout', {
    items: [
      { product_id: 1, qty: 2, price: 100 }
    ]
  });
  
  // Handle success
} catch (error) {
  // Handle error
}
```

#### Using Livewire (PHP Components)

Livewire components typically don't make direct API calls. Instead, they interact with services and models on the backend.

---

## State Management

### Livewire Component State

Livewire components manage state through public properties:

```php
class CustomersIndex extends Component
{
    // URL-bound properties (persist in URL)
    #[Url]
    public string $search = '';
    
    #[Url]
    public string $status = '';
    
    // Component-level state
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
    // Methods to update state
    public function updatingSearch(): void
    {
        // Reset pagination when search changes
        $this->resetPage();
    }
}
```

### JavaScript State (Alpine.js)

For components using Alpine.js or custom JavaScript:

```javascript
export function erpPosTerminal(options) {
    return {
        // State properties
        branchId: options.branchId,
        search: '',
        cart: [],
        isSearching: false,
        isCheckingOut: false,
        
        // Initialization
        init() {
            this.loadCart();
        },
        
        // State persistence
        persistCart() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
        },
        
        loadCart() {
            const raw = localStorage.getItem(this.storageKey);
            this.cart = raw ? JSON.parse(raw) : [];
        }
    };
}
```

### State Persistence

1. **LocalStorage**: Used for POS cart and offline queue
2. **URL Parameters**: Used for filters and search (via Livewire #[Url] attribute)
3. **Session**: Managed by Laravel for authentication and flash messages
4. **Cache**: Used for expensive queries (e.g., statistics)

---

## Component Patterns

### Livewire Component Structure

```php
namespace App\Livewire\[Module];

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class Index extends Component
{
    use WithPagination;
    
    // State properties
    #[Url]
    public string $search = '';
    
    // Lifecycle hooks
    public function mount(): void
    {
        $this->authorize('[permission]');
    }
    
    // Event handlers
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    // Render method
    public function render()
    {
        $items = Model::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->paginate(15);
            
        return view('livewire.[module].index', [
            'items' => $items,
        ]);
    }
}
```

### Blade Component Structure

```blade
{{-- Reusable component: resources/views/components/loading-indicator.blade.php --}}
@props([
    'target' => null,
    'text' => null,
    'fullscreen' => false,
])

<div wire:loading @if($target) wire:target="{{ $target }}" @endif>
    {{-- Loading indicator markup --}}
</div>

{{-- Usage --}}
<x-loading-indicator target="save" :fullscreen="true" :text="__('Saving...')" />
```

---

## Form Handling

### Basic Form Pattern

```blade
<form wire:submit="save">
    {{-- Loading indicator --}}
    <x-loading-indicator target="save" :fullscreen="true" />
    
    <div>
        <label>{{ __('Field Name') }} <span class="text-red-500">*</span></label>
        <input type="text" 
               wire:model="fieldName" 
               class="erp-input @error('fieldName') border-red-500 @enderror"
               required>
        @error('fieldName') 
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
        @enderror
    </div>
    
    <button type="submit" 
            wire:loading.attr="disabled" 
            wire:target="save"
            class="erp-btn erp-btn-primary">
        <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
        <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
    </button>
</form>
```

### Form Component (PHP)

```php
class Form extends Component
{
    use HandlesErrors; // Custom trait for error handling
    
    public ?Model $model = null;
    public bool $editMode = false;
    
    // Form fields
    public string $name = '';
    public string $email = '';
    
    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:table,email,' . $this->model?->id,
        ];
    }
    
    public function mount(?Model $model = null): void
    {
        if ($model && $model->exists) {
            $this->model = $model;
            $this->editMode = true;
            $this->fill($model->toArray());
        }
    }
    
    public function save(): void
    {
        $validated = $this->validate();
        
        $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->model->update($validated);
                } else {
                    Model::create($validated);
                }
            },
            successMessage: __('Saved successfully'),
            redirectRoute: 'models.index'
        );
    }
}
```

---

## Error Handling

### Frontend Error Handling (JavaScript)

```javascript
try {
    const response = await window.axios.post(url, data, {
        timeout: 15000
    });
    
    // Handle success
    this.message = {
        type: 'success',
        text: response.data.message
    };
    
} catch (error) {
    console.error('Error:', error);
    
    let errorMessage = 'An error occurred';
    
    if (error.code === 'ECONNABORTED') {
        errorMessage = 'Request timeout. Please try again.';
    } else if (error.response) {
        const status = error.response.status;
        
        if (status === 401) {
            errorMessage = 'Session expired. Please login again.';
        } else if (status === 403) {
            errorMessage = 'You do not have permission.';
        } else if (status === 422) {
            // Validation errors
            if (error.response.data?.errors) {
                const errors = Object.values(error.response.data.errors).flat();
                errorMessage = errors.join(' ');
            } else if (error.response.data?.message) {
                errorMessage = error.response.data.message;
            }
        } else if (status >= 500) {
            errorMessage = 'Server error. Please try again later.';
        } else if (error.response.data?.message) {
            errorMessage = error.response.data.message;
        }
    } else if (error.request) {
        errorMessage = 'Cannot connect to server. Check your connection.';
    }
    
    this.message = {
        type: 'error',
        text: errorMessage
    };
}
```

### Backend Error Handling (Livewire)

```php
public function save(): void
{
    try {
        $validated = $this->validate();
        
        DB::transaction(function () use ($validated) {
            // Perform operations
        });
        
        $this->dispatch('swal:success', [
            'message' => __('Saved successfully')
        ]);
        
        $this->redirect(route('index'), navigate: true);
        
    } catch (ValidationException $e) {
        // Livewire handles validation errors automatically
        throw $e;
    } catch (\Exception $e) {
        Log::error('Save error', [
            'error' => $e->getMessage(),
            'user' => auth()->id()
        ]);
        
        $this->dispatch('swal:error', [
            'message' => __('An error occurred. Please try again.')
        ]);
    }
}
```

---

## Loading States

### Livewire Loading States

```blade
{{-- Show/hide elements during loading --}}
<div wire:loading wire:target="save">
    {{ __('Saving...') }}
</div>

<div wire:loading.remove wire:target="save">
    {{ __('Save') }}
</div>

{{-- Disable buttons during loading --}}
<button wire:loading.attr="disabled" wire:target="save">
    {{ __('Save') }}
</button>

{{-- Add CSS classes during loading --}}
<button wire:loading.class="opacity-50" wire:target="save">
    {{ __('Save') }}
</button>

{{-- Global loading indicator --}}
<x-loading-indicator target="save" :fullscreen="true" :text="__('Please wait...')" />
```

### JavaScript Loading States

```javascript
async fetchData() {
    // Prevent concurrent requests
    if (this.isLoading) {
        return;
    }
    
    this.isLoading = true;
    this.clearMessage();
    
    try {
        const response = await window.axios.get(url);
        this.data = response.data.data;
    } catch (error) {
        // Handle error
    } finally {
        this.isLoading = false;
    }
}
```

---

## Validation

### Frontend Validation (HTML5)

```blade
<input type="text" 
       wire:model="name" 
       required 
       maxlength="255"
       pattern="[A-Za-z ]+"
       class="erp-input">

<input type="email" 
       wire:model="email" 
       required
       class="erp-input">

<input type="number" 
       wire:model="quantity" 
       required
       min="1"
       max="999999"
       step="0.01"
       class="erp-input">
```

### Backend Validation (Livewire)

```php
protected function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $this->user?->id,
        'quantity' => 'required|numeric|min:0.01|max:999999',
        'price' => 'required|numeric|min:0|max:9999999.99',
        'date' => 'required|date|after_or_equal:today',
    ];
}

protected function messages(): array
{
    return [
        'name.required' => __('Name is required'),
        'email.unique' => __('Email already exists'),
    ];
}
```

### JavaScript Validation

```javascript
updateQty(index, qty) {
    const value = Number(qty ?? 0);
    
    // Validate
    if (isNaN(value) || value <= 0) {
        this.cart[index].qty = 1;
        this.message = {
            type: 'error',
            text: 'Invalid quantity'
        };
        return;
    }
    
    if (value > 999999) {
        this.cart[index].qty = 999999;
        this.message = {
            type: 'warning',
            text: 'Maximum quantity is 999999'
        };
        return;
    }
    
    this.cart[index].qty = value;
    this.persistCart();
}
```

---

## Security Considerations

### CSRF Protection

All forms automatically include CSRF tokens via Livewire or Blade:

```blade
{{-- Livewire forms (automatic) --}}
<form wire:submit="save">
    {{-- CSRF token added automatically --}}
</form>

{{-- Regular forms --}}
<form method="POST" action="{{ route('action') }}">
    @csrf
    {{-- Form fields --}}
</form>
```

Axios is configured to include CSRF token in headers:

```javascript
// resources/js/bootstrap.js
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.head.querySelector('meta[name="csrf-token"]').content;
```

### Authorization Checks

```php
// In Livewire components
public function mount(): void
{
    $this->authorize('permission.name');
}

// In Blade views
@can('permission.name')
    <button>{{ __('Action') }}</button>
@endcan

@cannot('permission.name')
    <p>{{ __('No permission') }}</p>
@endcannot
```

### XSS Prevention

Blade automatically escapes output:

```blade
{{-- Escaped (safe) --}}
<p>{{ $userInput }}</p>

{{-- Unescaped (dangerous - use only for trusted content) --}}
<div>{!! $trustedHtml !!}</div>
```

### Input Sanitization

Always validate and sanitize user input on the backend:

```php
$validated = $this->validate([
    'content' => 'required|string|max:1000',
]);

// Strip HTML tags if not needed
$clean = strip_tags($validated['content']);

// Or use HTMLPurifier for rich text
$clean = clean($validated['content']);
```

---

## Performance Optimization

### Lazy Loading

```php
// Eager load relationships to avoid N+1 queries
$sales = Sale::with(['customer', 'items', 'payments'])->paginate(15);

// Lazy eager load conditionally
$sales->load('refunds');
```

### Caching

```php
public function getStatistics(): array
{
    return Cache::remember('sales_stats_' . auth()->user()->branch_id, 300, function () {
        return [
            'total_sales' => Sale::count(),
            'total_revenue' => Sale::sum('grand_total'),
        ];
    });
}
```

### Query Optimization

```php
// Use select() to limit columns
$products = Product::select('id', 'name', 'price', 'quantity')->get();

// Use chunk() for large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process
    }
});
```

### Asset Optimization

```javascript
// Code splitting (Vite handles automatically)
import('./large-module.js').then(module => {
    module.init();
});
```

---

## Common Patterns

### Notification System

```php
// Success notification
$this->dispatch('swal:success', [
    'message' => __('Operation successful'),
    'playSound' => true
]);

// Error notification
$this->dispatch('swal:error', [
    'message' => __('Operation failed')
]);

// Confirmation dialog
$this->dispatch('swal:confirm', [
    'title' => __('Are you sure?'),
    'text' => __('This action cannot be undone'),
    'confirmText' => __('Yes, delete it'),
    'cancelText' => __('Cancel'),
    'callback' => 'deleteConfirmed',
    'params' => ['id' => $id]
]);
```

### Modal Pattern

```blade
<div x-data="{ open: false }">
    <button @click="open = true">{{ __('Open Modal') }}</button>
    
    <div x-show="open" 
         @click.away="open = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6">
            <h2>{{ __('Modal Title') }}</h2>
            <button @click="open = false">{{ __('Close') }}</button>
        </div>
    </div>
</div>
```

### Search and Filter Pattern

```php
class Index extends Component
{
    use WithPagination;
    
    #[Url]
    public string $search = '';
    
    #[Url]
    public string $status = '';
    
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $items = Model::query()
            ->when($this->search, fn($q) => 
                $q->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->status, fn($q) => 
                $q->where('status', $this->status)
            )
            ->paginate(15);
            
        return view('livewire.index', ['items' => $items]);
    }
}
```

### Export Pattern

```php
use App\Traits\HasExport;

class Index extends Component
{
    use HasExport;
    
    public function mount(): void
    {
        $this->initializeExport('export_name');
    }
    
    public function export()
    {
        $data = Model::query()->get();
        
        return $this->performExport('filename', $data, __('Export Title'));
    }
}
```

---

## Troubleshooting

### Common Issues

1. **Livewire component not updating**
   - Ensure property is public
   - Check wire:model binding
   - Verify wire:key on loops

2. **Form not submitting**
   - Check wire:submit or wire:submit.prevent
   - Verify authorization in mount()
   - Check validation rules

3. **JavaScript not working**
   - Run `npm run build` after changes
   - Check browser console for errors
   - Verify Vite is running in development

4. **API returning 401/403**
   - Check authentication (auth:sanctum middleware)
   - Verify permissions in controller
   - Check CSRF token

---

## Best Practices

1. **Always validate on backend** - Never trust frontend validation alone
2. **Use loading indicators** - Provide feedback during async operations
3. **Handle errors gracefully** - Show user-friendly error messages
4. **Prevent double submits** - Disable buttons during submission
5. **Use eager loading** - Avoid N+1 query problems
6. **Cache expensive queries** - Use Laravel's cache for statistics
7. **Follow naming conventions** - Use consistent naming for clarity
8. **Add authorization checks** - Verify permissions at every entry point
9. **Log errors** - Use Laravel's logging for debugging
10. **Test thoroughly** - Test all user flows and edge cases

---

For more information, refer to:
- [Laravel Documentation](https://laravel.com/docs)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)
