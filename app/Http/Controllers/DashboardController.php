<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\MenuOption;
use App\Models\Permission;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 1)->count();
        $inactiveUsers = User::where('status', 0)->count();
        $totalRoles = Role::count();
        $totalModules = MenuOption::count();

        // Datos para RosenCharts (D3.js)
        $donutData = [
            ['label' => 'Activos', 'value' => $activeUsers, 'color' => '#10b981'],
            ['label' => 'Inactivos', 'value' => $inactiveUsers, 'color' => '#64748b'],
        ];

        // Datos de Actividad Mensual (Area / Bar Chart)
        $areaData = [
            ['period' => 'Ene', 'value' => 12],
            ['period' => 'Feb', 'value' => 19],
            ['period' => 'Mar', 'value' => 25],
            ['period' => 'Abr', 'value' => 32],
            ['period' => 'May', 'value' => 28],
            ['period' => 'Jun', 'value' => 45],
        ];

        // Datos de Tendencias (Line Chart)
        $lineData = [
            ['label' => 'S1', 'value' => 5],
            ['label' => 'S2', 'value' => 14],
            ['label' => 'S3', 'value' => 22],
            ['label' => 'S4', 'value' => 30],
            ['label' => 'S5', 'value' => 28],
            ['label' => 'S6', 'value' => 40],
        ];

        $recentUsers = User::with('profile.role')->latest()->take(5)->get();

        return view('dashboard', compact(
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'totalRoles',
            'totalModules',
            'donutData',
            'areaData',
            'lineData',
            'recentUsers'
        ));
    }
}
