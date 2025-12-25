<?php

namespace App\Livewire\Admin\Translations;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedGroup = '';
    
    protected $queryString = ['search', 'selectedGroup'];
    
    public function mount()
    {
        $this->loadGroups();
    }
    
    public function loadGroups()
    {
        return $this->getTranslationGroups();
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
    
    public function getTranslations()
    {
        $translations = [];
        $locales = ['en', 'ar'];
        
        foreach ($locales as $locale) {
            $langPath = lang_path($locale);
            
            if (!File::isDirectory($langPath)) {
                continue;
            }
            
            $files = File::files($langPath);
            
            foreach ($files as $file) {
                $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                
                // Filter by selected group
                if ($this->selectedGroup && $group !== $this->selectedGroup) {
                    continue;
                }
                
                $content = include $file->getPathname();
                
                if (is_array($content)) {
                    $this->flattenTranslations($content, $group, $locale, $translations);
                }
            }
        }
        
        // Filter by search term
        if ($this->search) {
            $search = strtolower($this->search);
            $translations = array_filter($translations, function ($item) use ($search) {
                return str_contains(strtolower($item['key']), $search) ||
                       str_contains(strtolower($item['en'] ?? ''), $search) ||
                       str_contains(strtolower($item['ar'] ?? ''), $search);
            });
        }
        
        return collect($translations)->values();
    }
    
    protected function flattenTranslations($array, $group, $locale, &$translations, $prefix = '')
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $translationKey = "{$group}.{$fullKey}";
            
            if (is_array($value)) {
                $this->flattenTranslations($value, $group, $locale, $translations, $fullKey);
            } else {
                if (!isset($translations[$translationKey])) {
                    $translations[$translationKey] = [
                        'key' => $translationKey,
                        'group' => $group,
                        'en' => '',
                        'ar' => '',
                    ];
                }
                $translations[$translationKey][$locale] = $value;
            }
        }
    }
    
    public function clearCache()
    {
        try {
            // Clear translation-related caches
            Cache::forget('translations.en');
            Cache::forget('translations.ar');
            
            // Clear all cached translations
            Cache::flush();
            
            // Clear config cache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Translation cache cleared!'),
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Failed to clear cache: ') . $e->getMessage(),
            ]);
        }
    }
    
    public function deleteTranslation($key, $group)
    {
        // Extract just the key without group prefix
        $keyParts = explode('.', $key);
        array_shift($keyParts); // Remove group prefix
        $keyWithoutGroup = implode('.', $keyParts);
        
        // Delete from both language files
        $this->removeFromFile('en', $group, $keyWithoutGroup);
        $this->removeFromFile('ar', $group, $keyWithoutGroup);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Translation deleted successfully.'),
        ]);
    }
    
    protected function removeFromFile($locale, $group, $key)
    {
        $filePath = lang_path("{$locale}/{$group}.php");
        
        if (!File::exists($filePath)) {
            return;
        }
        
        $translations = include $filePath;
        
        // Handle nested keys
        $keys = explode('.', $key);
        $this->removeNestedKey($translations, $keys);
        
        // Save to file
        $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
        File::put($filePath, $content);
    }
    
    protected function removeNestedKey(&$array, $keys)
    {
        $key = array_shift($keys);
        
        if (empty($keys)) {
            unset($array[$key]);
        } elseif (isset($array[$key]) && is_array($array[$key])) {
            $this->removeNestedKey($array[$key], $keys);
            // Remove empty parent arrays
            if (empty($array[$key])) {
                unset($array[$key]);
            }
        }
    }
    
    public function getMissingTranslations()
    {
        $missing = [];
        
        // Scan blade files for hardcoded strings
        $viewsPath = resource_path('views');
        $files = File::allFiles($viewsPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            $content = File::get($file->getPathname());
            
            // Find strings that might need translation (simple heuristic)
            // Look for >'text'< or >"text"< patterns (text between tags)
            preg_match_all('/>\s*([A-Z][a-zA-Z\s]{3,30})\s*</', $content, $matches);
            
            foreach ($matches[1] ?? [] as $text) {
                $text = trim($text);
                if ($text && !preg_match('/^[A-Z_]+$/', $text)) {
                    $missing[] = $text;
                }
            }
        }
        
        return array_unique($missing);
    }
    
    public function render()
    {
        return view('livewire.admin.translations.index', [
            'translations' => $this->getTranslations(),
            'groups' => $this->getTranslationGroups(),
            'missingCount' => count($this->getMissingTranslations()),
        ]);
    }
}
