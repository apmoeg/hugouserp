<nav class="h-full overflow-y-auto bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
    @php
        $currentUser = auth()->user();
    @endphp
    <div class="px-3 py-4">
        <!-- Logo/Brand -->
        <div class="mb-6 px-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">HugouERP</h2>
        </div>

        <ul class="space-y-1">
            <!-- Dashboard -->
            <x-sidebar.item route="dashboard" icon="home" label="Dashboard" />

            <!-- POS -->
            @can('pos.use')
                <x-sidebar.item route="pos.terminal" icon="calculator" label="POS Terminal" />
            @endcan

            <!-- Divider -->
            <li class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Business Modules') }}</span>
            </li>

            <!-- Sales -->
            @can('sales.view')
                <x-sidebar.item route="app.sales.index" icon="shopping-cart" label="Sales" />
            @endcan

            <!-- Purchases -->
            @can('purchases.view')
                <x-sidebar.item route="app.purchases.index" icon="shopping-bag" label="Purchases" />
            @endcan

            <!-- Inventory -->
            @can('inventory.products.view')
                <x-sidebar.item route="app.inventory.index" icon="cube" label="Inventory" />
            @endcan

            <!-- Warehouse -->
            @can('warehouse.view')
                <x-sidebar.item route="app.warehouse.index" icon="warehouse" label="Warehouse" />
            @endcan

            <!-- Accounting -->
            @can('accounting.view')
                <x-sidebar.item route="app.accounting.index" icon="calculator" label="Accounting" />
            @endcan

            <!-- Expenses -->
            @can('expenses.view')
                <x-sidebar.item route="app.expenses.index" icon="money" label="Expenses" />
            @endcan

            <!-- Income -->
            @can('income.view')
                <x-sidebar.item route="app.income.index" icon="money" label="Income" />
            @endcan

            <!-- Divider -->
            <li class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Contacts') }}</span>
            </li>

            <!-- Customers -->
            @can('customers.view')
                <x-sidebar.item route="customers.index" icon="users" label="Customers" />
            @endcan

            <!-- Suppliers -->
            @can('suppliers.view')
                <x-sidebar.item route="suppliers.index" icon="truck" label="Suppliers" />
            @endcan

            <!-- Divider -->
            <li class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Operations') }}</span>
            </li>

            <!-- HRM -->
            @can('hrm.employees.view')
                <x-sidebar.item route="app.hrm.index" icon="users" label="Human Resources" />
            @endcan

            <!-- Rental -->
            @can('rental.units.view')
                <x-sidebar.item route="app.rental.index" icon="home" label="Rental" />
            @endcan

            <!-- Manufacturing -->
            @can('manufacturing.view')
                <x-sidebar.item route="app.manufacturing.index" icon="cog" label="Manufacturing" />
            @endcan

            <!-- Banking -->
            @can('banking.view')
                <x-sidebar.item route="app.banking.index" icon="bank" label="Banking" />
            @endcan

            <!-- Fixed Assets -->
            @can('fixed-assets.view')
                <x-sidebar.item route="app.fixed-assets.index" icon="building" label="Fixed Assets" />
            @endcan

            <!-- Projects -->
            @can('projects.view')
                <x-sidebar.item route="app.projects.index" icon="briefcase" label="Projects" />
            @endcan

            <!-- Documents -->
            @can('documents.view')
                <x-sidebar.item route="app.documents.index" icon="document" label="Documents" />
            @endcan

            <!-- Helpdesk -->
            @can('helpdesk.view')
                <x-sidebar.item route="app.helpdesk.index" icon="support" label="Helpdesk" />
            @endcan

            {{-- Employee Self-Service Section --}}
            @php
                $hasSelfService = $currentUser?->can('employee.self.attendance') || 
                                  $currentUser?->can('employee.self.leave-request') || 
                                  $currentUser?->can('employee.self.payslip-view');
            @endphp
            @if($hasSelfService)
            <li class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Self Service') }}</span>
            </li>

            @can('employee.self.attendance')
                <x-sidebar.item route="app.hrm.my-attendance" icon="clock" label="My Attendance" />
            @endcan

            @can('employee.self.leave-request')
                <x-sidebar.item route="app.hrm.my-leaves" icon="calendar" label="My Leaves" />
            @endcan

            @can('employee.self.payslip-view')
                <x-sidebar.item route="app.hrm.my-payslips" icon="document" label="My Payslips" />
            @endcan
            @endif

            <!-- Divider -->
            <li class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Administration') }}</span>
            </li>

            <!-- Settings -->
            @can('settings.view')
                <x-sidebar.item route="admin.settings" icon="cog" label="Settings" />
            @endcan

            <!-- Branch Settings (for Branch Admins) -->
            @can('branch.settings.manage')
                <x-sidebar.item route="admin.branch-settings" icon="building" label="Branch Settings" />
            @endcan

            <!-- Reports -->
            @can('reports.view')
                <x-sidebar.item route="admin.reports.index" icon="chart" label="Reports" />
            @endcan

            <!-- Branch Reports (for Branch Managers without full reports access) -->
            @can('branch.reports.view')
                @cannot('reports.view')
                    <x-sidebar.item route="admin.branch-reports" icon="chart" label="Branch Reports" />
                @endcannot
            @endcan

            <!-- Users -->
            @can('users.manage')
                <x-sidebar.item route="admin.users.index" icon="users" label="Users" />
            @endcan

            <!-- Branch Employees (for Branch Admins without full users access) -->
            @can('branch.employees.manage')
                @cannot('users.manage')
                    <x-sidebar.item route="admin.branch-employees" icon="users" label="Branch Employees" />
                @endcannot
            @endcan

            <!-- Roles -->
            @can('roles.manage')
                <x-sidebar.item route="admin.roles.index" icon="shield" label="Roles" />
            @endcan

            <!-- Branches -->
            @can('branches.view')
                <x-sidebar.item route="admin.branches.index" icon="building" label="Branches" />
            @endcan

            <!-- Modules -->
            @can('modules.manage')
                <x-sidebar.item route="admin.modules.index" icon="puzzle" label="Modules" />
            @endcan

            <!-- Audit Logs -->
            @can('logs.audit.view')
                <x-sidebar.item route="admin.logs.audit" icon="list" label="Audit Logs" />
            @endcan
        </ul>
    </div>
</nav>
