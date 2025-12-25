<?php

namespace App\Livewire\Admin\Translations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public $translationKey = '';
    public $group = 'app';
    public $valueEn = '';
    public $valueAr = '';
    public $isEdit = false;
    public $originalKey = '';
    
    protected $queryString = ['key' => ['as' => 'translationKey'], 'group'];
    
    public function mount($key = null, $group = null)
    {
        if ($key && $group) {
            $this->isEdit = true;
            $this->originalKey = $key;
            $this->group = $group;
            
            // Load existing translation values
            $this->loadTranslation($key, $group);
        }
        
        // Handle query string parameters for editing
        if (request()->has('key') && request()->has('group')) {
            $this->isEdit = true;
            $this->originalKey = request()->get('key');
            $this->group = request()->get('group');
            $this->translationKey = $this->originalKey;
            $this->loadTranslation($this->originalKey, $this->group);
        }
    }
    
    protected function loadTranslation($key, $group)
    {
        // Extract just the key without group prefix
        $keyWithoutGroup = $key;
        if (str_starts_with($key, $group . '.')) {
            $keyWithoutGroup = substr($key, strlen($group) + 1);
        }
        
        $this->translationKey = $keyWithoutGroup;
        
        // Load English value
        $enPath = lang_path("en/{$group}.php");
        if (File::exists($enPath)) {
            $translations = include $enPath;
            $this->valueEn = $this->getNestedValue($translations, $keyWithoutGroup) ?? '';
        }
        
        // Load Arabic value
        $arPath = lang_path("ar/{$group}.php");
        if (File::exists($arPath)) {
            $translations = include $arPath;
            $this->valueAr = $this->getNestedValue($translations, $keyWithoutGroup) ?? '';
        }
    }
    
    protected function getNestedValue($array, $key)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return is_string($value) ? $value : null;
    }
    
    public function getTranslationGroups()
    {
        $groups = [];
        $langPath = lang_path('en');
        
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                $groups[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }
        }
        
        return array_unique($groups);
    }
    
    public function save()
    {
        $this->validate([
            'translationKey' => 'required|string|max:255|regex:/^[a-zA-Z0-9_.]+$/',
            'group' => 'required|string|max:50|alpha_dash',
            'valueEn' => 'required|string|max:2000',
            'valueAr' => 'required|string|max:2000',
        ], [
            'translationKey.regex' => __('Translation key can only contain letters, numbers, underscores, and dots.'),
        ]);
        
        // Sanitize the key to prevent code injection
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $this->translationKey);
        
        // Check if key already exists when adding new
        if (!$this->isEdit) {
            $fullKey = "{$this->group}.{$sanitizedKey}";
            if ($this->translationExists($this->group, $sanitizedKey)) {
                $this->addError('translationKey', __('This translation key already exists.'));
                return;
            }
        }
        
        // Save English translation
        $this->saveToFile('en', $this->group, $sanitizedKey, $this->valueEn);
        
        // Save Arabic translation
        $this->saveToFile('ar', $this->group, $sanitizedKey, $this->valueAr);
        
        // Clear cache
        Cache::forget('translations.en');
        Cache::forget('translations.ar');
        
        $message = $this->isEdit 
            ? __('Translation updated successfully.') 
            : __('Translation added successfully.');
        
        session()->flash('success', $message);
        
        return redirect()->route('admin.translations.index');
    }
    
    protected function translationExists($group, $key)
    {
        $filePath = lang_path("en/{$group}.php");
        
        if (!File::exists($filePath)) {
            return false;
        }
        
        $translations = include $filePath;
        return $this->getNestedValue($translations, $key) !== null;
    }
    
    protected function saveToFile($locale, $group, $key, $value)
    {
        $filePath = lang_path("{$locale}/{$group}.php");
        
        // Load existing translations or create empty array
        $translations = [];
        if (File::exists($filePath)) {
            $translations = include $filePath;
        }
        
        // Handle nested keys
        $keys = explode('.', $key);
        $current = &$translations;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
        
        // Save to file using var_export for safe PHP array generation
        $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
        
        // Ensure directory exists
        $directory = dirname($filePath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        File::put($filePath, $content);
    }
    
    public function cancel()
    {
        return redirect()->route('admin.translations.index');
    }
    
    public function render()
    {
        return view('livewire.admin.translations.form', [
            'groups' => $this->getTranslationGroups(),
        ]);
    }
}
