<?php declare(strict_types=1);

/**
 * Application Routes
 *
 * @var \App\Core\Router $router
 */

// ----------------------------------------------------------------
// Auth routes (no authentication required)
// ----------------------------------------------------------------
$router->get('/',                           'AuthController@loginForm',       false);
$router->get('/login',                      'AuthController@loginForm',       false);
$router->post('/login',                     'AuthController@login',           false);
$router->get('/logout',                     'AuthController@logout',          false);
$router->get('/forgot-password',            'AuthController@forgotPassword',  false);
$router->post('/forgot-password',           'AuthController@sendResetEmail',  false);
$router->get('/reset-password/{token}',     'AuthController@resetPasswordForm', false);
$router->post('/reset-password',            'AuthController@resetPassword',   false);

// ----------------------------------------------------------------
// Dashboard
// ----------------------------------------------------------------
$router->get('/dashboard', 'DashboardController@index');

// ----------------------------------------------------------------
// Movements
// ----------------------------------------------------------------
$router->get('/movimentacoes',                      'MovementController@index');
$router->get('/movimentacoes/criar',                'MovementController@create');
$router->post('/movimentacoes',                     'MovementController@store');
$router->get('/movimentacoes/fixas',                'MovementController@fixedIndex');
$router->post('/movimentacoes/fixas',               'MovementController@fixedStore');
$router->post('/movimentacoes/fixas/{id}',          'MovementController@fixedUpdate');
$router->post('/movimentacoes/fixas/{id}/toggle',   'MovementController@fixedToggle');
$router->get('/movimentacoes/{id}/editar',          'MovementController@edit');
$router->post('/movimentacoes/{id}',                'MovementController@update');
$router->post('/movimentacoes/{id}/excluir',        'MovementController@destroy');
$router->post('/movimentacoes/{id}/validar',        'MovementController@validate');
$router->post('/movimentacoes/{id}/reverter',       'MovementController@revert');
$router->post('/movimentacoes/dividas-parceladas/registrar', 'MovementController@storeDebtPayment');

// ----------------------------------------------------------------
// Credit cards
// ----------------------------------------------------------------
$router->get('/cartoes',                        'CardController@index');
$router->post('/cartoes',                       'CardController@store');
$router->post('/cartoes/{id}/excluir',          'CardController@destroy');
$router->get('/cartoes/{id}/movimentos',                        'CardController@movements');
$router->post('/cartoes/{id}/movimentos',                       'CardController@storeMovement');
$router->get('/cartoes/{cardId}/movimentos/{movId}/json',       'CardController@getMovementJson');
$router->post('/cartoes/{cardId}/movimentos/{movId}',           'CardController@updateMovement');
$router->post('/cartoes/{cardId}/movimentos/{movId}/excluir',   'CardController@destroyMovement');
$router->post('/cartoes/{id}/fechar',                           'CardController@closeMonth');

// ----------------------------------------------------------------
// Payroll
// ----------------------------------------------------------------
$router->get('/folha-pagamento',              'PayrollController@index');
$router->post('/folha-pagamento',             'PayrollController@store');
$router->get('/folha-pagamento/{id}/editar',  'PayrollController@edit');
$router->post('/folha-pagamento/{id}',        'PayrollController@update');
$router->post('/folha-pagamento/{id}/excluir','PayrollController@destroy');

// ----------------------------------------------------------------
// Installment debts
// ----------------------------------------------------------------
$router->get('/dividas-parceladas',             'InstallmentDebtController@index');
$router->post('/dividas-parceladas',            'InstallmentDebtController@store');
$router->post('/dividas-parceladas/pagamentos', 'InstallmentDebtController@registerPayment');

// ----------------------------------------------------------------
// Reports
// ----------------------------------------------------------------
$router->get('/relatorios',                    'ReportController@index');
$router->get('/relatorios/categorias/{tipo}',  'ReportController@byCategory');
$router->get('/relatorios/mensal',             'ReportController@monthly');
$router->get('/relatorios/fluxo',              'ReportController@cashflow');
$router->get('/relatorios/exportar/{type}',    'ReportController@exportCsv');

// ----------------------------------------------------------------
// User / Profile
// ----------------------------------------------------------------
$router->get('/perfil',          'UserController@profile');
$router->post('/perfil',         'UserController@updateProfile');
$router->post('/perfil/senha',   'UserController@changePassword');

// ----------------------------------------------------------------
// API: subcategorias (AJAX)
// ----------------------------------------------------------------
$router->get('/api/subcategorias/{categoriaId}', function (string $categoriaId) {
    $model = new \App\Models\Subcategory();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($model->findByCategory((int) $categoriaId));
    exit;
});
