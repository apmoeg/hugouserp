// resources/js/pos.js
/**
 * POS Terminal Frontend Logic
 * 
 * This module handles the Point of Sale (POS) terminal functionality including:
 * - Product search via API
 * - Shopping cart management with localStorage persistence
 * - Checkout process via API
 * - Offline mode with queue synchronization
 * 
 * The POS terminal is designed to work both online and offline, storing transactions
 * in localStorage when offline and syncing them when connection is restored.
 * 
 * @param {Object} options - Configuration options
 * @param {number} options.branchId - The branch ID for this POS terminal
 * @returns {Object} Alpine.js component data object
 */

// Configuration Constants
const CONFIG = {
    /** Maximum allowed quantity per cart item */
    MAX_QUANTITY: 999999,
    
    /** Maximum allowed price per item */
    MAX_PRICE: 9999999.99,
    
    /** Timeout for product search requests (milliseconds) */
    SEARCH_TIMEOUT: 10000,
    
    /** Timeout for checkout requests (milliseconds) */
    CHECKOUT_TIMEOUT: 15000,
};

export function erpPosTerminal(options) {
    const branchId = options.branchId;

    return {
        // ========== State Properties ==========
        
        /** @type {number} Current branch ID */
        branchId,
        
        /** @type {string} Current search query for products */
        search: '',
        
        /** @type {boolean} Whether a product search is in progress */
        isSearching: false,
        
        /** @type {Array} List of products matching current search */
        products: [],
        
        /** @type {Array} Shopping cart items */
        cart: [],
        
        /** @type {boolean} Whether checkout is in progress */
        isCheckingOut: false,
        
        /** @type {Object|null} Current user message {type: 'success'|'error'|'info', text: string} */
        message: null,
        
        /** @type {boolean} Whether the system is offline */
        offline: !window.navigator.onLine,

        /** @type {Array} Queue of offline sales to sync when connection is restored */
        offlineQueue: [],

        // ========== Lifecycle Methods ==========
        
        /**
         * Initialize the POS terminal
         * Sets up cart persistence and online/offline event listeners
         */
        init() {
            this.loadCart();
            this.loadOfflineQueue();

            // Listen for connection status changes
            window.addEventListener('online', () => {
                this.offline = false;
            });

            window.addEventListener('offline', () => {
                this.offline = true;
            });
        },

        // ========== Storage Keys ==========
        
        /**
         * Get the localStorage key for the cart
         * Cart data is stored per branch to avoid conflicts
         * @returns {string} Storage key
         */
        get storageKey() {
            return `erp_pos_cart_branch_${this.branchId}`;
        },

        /**
         * Get the localStorage key for the offline queue
         * Offline sales are queued per branch for later synchronization
         * @returns {string} Storage key
         */
        get offlineQueueKey() {
            return `erp_pos_offline_sales_branch_${this.branchId}`;
        },

        // ========== Cart Persistence Methods ==========
        
        /**
         * Load cart from localStorage
         * Restores the cart state when the page is refreshed
         */
        loadCart() {
            try {
                const raw = window.localStorage.getItem(this.storageKey);
                this.cart = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('Failed to load POS cart from storage', e);
                this.cart = [];
            }
        },

        /**
         * Persist cart to localStorage
         * Saves the current cart state for later retrieval
         */
        persistCart() {
            try {
                window.localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
            } catch (e) {
                console.error('Failed to persist POS cart to storage', e);
            }
        },

        loadOfflineQueue() {
            try {
                const raw = window.localStorage.getItem(this.offlineQueueKey);
                this.offlineQueue = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('Failed to load POS offline queue', e);
                this.offlineQueue = [];
            }
        },

        persistOfflineQueue() {
            try {
                window.localStorage.setItem(this.offlineQueueKey, JSON.stringify(this.offlineQueue ?? []));
            } catch (e) {
                console.error('Failed to persist POS offline queue', e);
            }
        },

        
        async syncOfflineQueue() {
            if (!this.offlineQueue || !this.offlineQueue.length) {
                this.message = {
                    type: 'info',
                    text: 'لا توجد طلبات Offline للمزامنة.',
                };
                return;
            }

            const queue = [...this.offlineQueue];

            for (const item of queue) {
                try {
                    await window.axios.post(`/api/v1/branches/${this.branchId}/pos/checkout`, item.payload ?? {});
                    
                    // Remove synced item from queue and persist immediately
                    this.offlineQueue.shift();
                    this.persistOfflineQueue();
                } catch (error) {
                    console.error('Failed to sync offline sale', error);
                    this.message = {
                        type: 'error',
                        text: 'حدث خطأ أثناء مزامنة بعض طلبات الـ Offline.',
                    };
                    return;
                }
            }

            this.message = {
                type: 'success',
                text: 'تمت مزامنة كل الطلبات الـ Offline بنجاح.',
            };
        },

        get total() {
            return (this.cart || []).reduce((sum, item) => {
                const qty = Number(item.qty ?? 0);
                const price = Number(item.price ?? 0);
                return sum + qty * price;
            }, 0);
        },

        clearMessage() {
            this.message = null;
        },

        async fetchProducts() {
            // Validation: minimum search length
            if (!this.search || this.search.length < 2) {
                this.products = [];
                return;
            }

            // Check online status
            if (!window.navigator.onLine) {
                this.message = {
                    type: 'info',
                    text: 'الاتصال غير متاح، لا يمكن البحث عن المنتجات الآن.',
                };
                return;
            }

            // Prevent concurrent searches
            if (this.isSearching) {
                return;
            }

            this.isSearching = true;
            this.products = [];
            this.clearMessage();

            try {
                const response = await window.axios.get(`/api/v1/branches/${this.branchId}/products/search`, {
                    params: { q: this.search },
                    timeout: CONFIG.SEARCH_TIMEOUT,
                });

                let data = response.data;

                // Handle standardized API response format
                if (data && data.success === true && Array.isArray(data.data)) {
                    this.products = data.data;
                } else if (Array.isArray(data)) {
                    // Fallback for direct array response
                    this.products = data;
                } else if (data && Array.isArray(data.data)) {
                    // Fallback for nested data
                    this.products = data.data;
                } else {
                    this.products = [];
                    console.warn('Unexpected API response format:', data);
                }
            } catch (error) {
                console.error('POS search error', error);
                
                // Handle specific error types
                let errorMessage = 'حدث خطأ أثناء البحث عن المنتجات.';
                if (error.code === 'ECONNABORTED') {
                    errorMessage = 'انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.';
                } else if (error.response) {
                    // Server responded with error
                    if (error.response.status === 401) {
                        errorMessage = 'انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.';
                    } else if (error.response.status === 403) {
                        errorMessage = 'ليس لديك صلاحية للبحث عن المنتجات.';
                    } else if (error.response.status >= 500) {
                        errorMessage = 'خطأ في الخادم. يرجى المحاولة لاحقاً.';
                    } else if (error.response.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                } else if (error.request) {
                    // Request made but no response
                    errorMessage = 'لا يمكن الاتصال بالخادم. يرجى التحقق من الاتصال.';
                }

                this.message = {
                    type: 'error',
                    text: errorMessage,
                };
            } finally {
                this.isSearching = false;
            }
        },

        addProduct(product) {
            if (!product) {
                return;
            }

            const id = product.id ?? product.product_id;
            if (!id) {
                return;
            }

            const existingIndex = this.cart.findIndex((item) => item.product_id === id);

            if (existingIndex !== -1) {
                this.cart[existingIndex].qty = Number(this.cart[existingIndex].qty ?? 0) + 1;
            } else {
                this.cart.push({
                    product_id: id,
                    name: product.name ?? product.label ?? 'Item',
                    qty: 1,
                    price: Number(product.price ?? product.sale_price ?? 0),
                    discount: 0,
                    percent: false,
                    tax_id: product.tax_id ?? null,
                });
            }

            this.persistCart();
        },

        removeItem(index) {
            if (index < 0 || index >= this.cart.length) {
                return;
            }
            this.cart.splice(index, 1);
            this.persistCart();
        },

        updateQty(index, qty) {
            if (index < 0 || index >= this.cart.length) {
                return;
            }
            
            // Parse and validate quantity
            const value = Number(qty ?? 0);
            
            // Ensure positive quantity, minimum 0.01, maximum CONFIG.MAX_QUANTITY
            if (isNaN(value) || value <= 0) {
                this.cart[index].qty = 1;
            } else if (value > CONFIG.MAX_QUANTITY) {
                this.cart[index].qty = CONFIG.MAX_QUANTITY;
                this.message = {
                    type: 'warning',
                    text: `الكمية القصوى المسموح بها هي ${CONFIG.MAX_QUANTITY}.`,
                };
            } else {
                this.cart[index].qty = value;
            }
            
            this.persistCart();
        },

        updatePrice(index, price) {
            if (index < 0 || index >= this.cart.length) {
                return;
            }
            
            // Parse and validate price
            const value = Number(price ?? 0);
            
            // Ensure non-negative price, maximum CONFIG.MAX_PRICE
            if (isNaN(value) || value < 0) {
                this.cart[index].price = 0;
            } else if (value > CONFIG.MAX_PRICE) {
                this.cart[index].price = CONFIG.MAX_PRICE;
                this.message = {
                    type: 'warning',
                    text: `السعر القصوى المسموح به هو ${CONFIG.MAX_PRICE}.`,
                };
            } else {
                // Round to 2 decimal places
                this.cart[index].price = Math.round(value * 100) / 100;
            }
            
            this.persistCart();
        },

        enqueueOfflineSale(payload) {
            if (!this.offlineQueue) {
                this.offlineQueue = [];
            }
            this.offlineQueue.push({
                payload,
                queued_at: new Date().toISOString(),
            });
            this.persistOfflineQueue();
        },

        async checkout() {
            // Validation: check cart has items
            if (!this.cart.length) {
                this.message = {
                    type: 'info',
                    text: 'لا توجد عناصر في السلة.',
                };
                return;
            }

            // Prevent double submit
            if (this.isCheckingOut) {
                return;
            }

            // Validate cart items
            const invalidItems = this.cart.filter(item => !item.product_id || Number(item.qty) <= 0);
            if (invalidItems.length > 0) {
                this.message = {
                    type: 'error',
                    text: 'بعض العناصر في السلة غير صالحة. يرجى التحقق من الكميات.',
                };
                return;
            }

            const items = this.cart.map((item) => ({
                product_id: item.product_id,
                qty: Number(item.qty ?? 1),
                price: Number(item.price ?? 0),
                discount: Number(item.discount ?? 0),
                percent: !!item.percent,
                tax_id: item.tax_id ?? null,
            }));

            const payload = {
                items,
            };

            // Offline: enqueue and clear cart
            if (this.offline) {
                this.enqueueOfflineSale(payload);
                this.cart = [];
                this.persistCart();
                this.message = {
                    type: 'info',
                    text: 'تم حفظ الطلب في وضع عدم الاتصال. سيتم مزامنته عند توفر الإنترنت.',
                };
                return;
            }

            this.isCheckingOut = true;
            this.clearMessage();

            try {
                const response = await window.axios.post(`/api/v1/branches/${this.branchId}/pos/checkout`, payload, {
                    timeout: CONFIG.CHECKOUT_TIMEOUT,
                });

                // Extract message from standardized API response
                const data = response.data ?? {};
                let msg = 'تم تنفيذ عملية البيع بنجاح.';
                
                if (data.success === true && data.message) {
                    msg = data.message;
                } else if (data.message) {
                    msg = data.message;
                } else if (data.status) {
                    msg = data.status;
                }

                // Clear cart on success
                this.cart = [];
                this.persistCart();

                this.message = {
                    type: 'success',
                    text: msg,
                };

                // Optional: Show sale details if available
                if (data.data && data.data.code) {
                    console.log('Sale created:', data.data.code);
                }
            } catch (error) {
                console.error('POS checkout error', error);
                
                // Handle specific error types
                let errorMessage = 'فشل تنفيذ عملية البيع.';
                if (error.code === 'ECONNABORTED') {
                    errorMessage = 'انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.';
                } else if (error.response) {
                    // Server responded with error
                    if (error.response.status === 401) {
                        errorMessage = 'انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.';
                    } else if (error.response.status === 403) {
                        errorMessage = 'ليس لديك صلاحية لإجراء عمليات البيع.';
                    } else if (error.response.status === 422) {
                        // Validation error
                        if (error.response.data?.errors) {
                            const errors = Object.values(error.response.data.errors).flat();
                            errorMessage = errors.join(' ');
                        } else if (error.response.data?.message) {
                            errorMessage = error.response.data.message;
                        }
                    } else if (error.response.status >= 500) {
                        errorMessage = 'خطأ في الخادم. يرجى المحاولة لاحقاً.';
                    } else if (error.response.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                } else if (error.request) {
                    // Request made but no response
                    errorMessage = 'لا يمكن الاتصال بالخادم. يرجى التحقق من الاتصال.';
                }

                this.message = {
                    type: 'error',
                    text: errorMessage,
                };
            } finally {
                this.isCheckingOut = false;
            }
        },
    };
}

window.erpPosTerminal = erpPosTerminal;
