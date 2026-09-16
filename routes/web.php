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
$router->get('/movimentacoes/{id}/json',            'MovementController@getData');
$router->post('/movimentacoes/{id}',                'MovementController@update');
$router->post('/movimentacoes/{id}/excluir',        'MovementController@destroy');
$router->post('/movimentacoes/{id}/validar',        'MovementController@validate');
$router->post('/movimentacoes/{id}/reverter',       'MovementController@revert');
$router->post('/movimentacoes/dividas-parceladas/registrar', 'MovementController@storeDebtPayment');

// ----------------------------------------------------------------
// Simple input/output module (independent)
// ----------------------------------------------------------------
$router->get('/modulo-simples',                 'SimpleEntryController@index');
$router->post('/modulo-simples',                'SimpleEntryController@store');
$router->post('/modulo-simples/{id}',           'SimpleEntryController@update');
$router->post('/modulo-simples/{id}/excluir',   'SimpleEntryController@destroy');
$router->get('/modulo-simples/exportar/csv',    'SimpleEntryController@exportCsv');

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
$router->post('/dividas-parceladas/{id}',           'InstallmentDebtController@update');
$router->post('/dividas-parceladas/{id}/quitar',    'InstallmentDebtController@settle');
$router->post('/dividas-parceladas/{id}/excluir',   'InstallmentDebtController@destroy');

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
// Gerenciamento de Apostas (acesso restrito por autorização ou link público)
// ----------------------------------------------------------------
$router->get('/apostas',                     'BetController@index');
$router->get('/apostas/dia/{date}',          'BetController@dayRecords');
$router->get('/apostas/extrato',             'BetController@statement');
$router->post('/apostas/simples',            'BetController@storeSimple');
$router->post('/apostas/multipla',           'BetController@storeMultiple');
$router->post('/apostas/{id}/finalizar',     'BetController@finalize');
$router->post('/apostas/{id}/excluir',       'BetController@destroy');
$router->post('/apostas/banca',              'BetController@bankMovementStore');
$router->post('/apostas/prospectos',                'BetController@prospectStore');
$router->post('/apostas/prospectos/{id}/excluir',   'BetController@prospectDestroy');
$router->post('/apostas/link/gerar',         'BetController@generateShareLink');
$router->post('/apostas/link/revogar',       'BetController@revokeShareLink');
$router->get('/apostas/compartilhado/{token}',              'BetController@publicView',        false);
$router->get('/apostas/compartilhado/{token}/dia/{date}',   'BetController@publicDayRecords',   false);

// ----------------------------------------------------------------
// API: subcategorias (AJAX)
// ----------------------------------------------------------------
$router->get('/api/subcategorias/{categoriaId}', function (string $categoriaId) {
    $model = new \App\Models\Subcategory();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($model->findByCategory((int) $categoriaId));
    exit;
});
