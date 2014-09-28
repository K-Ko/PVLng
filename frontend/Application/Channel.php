<?php
/**
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  2012-2014 Knut Kohl
 * @license    MIT License (MIT) http://opensource.org/licenses/MIT
 * @version    1.0.0
 */
if (!Session::get('User')) return;

/**
 *
 */
PVLng::Menu('10.20', '/channel', __('Channels'), 'Shift+F3');

PVLng::Menu('10.20.10', '/channel/add', __('CreateChannel'));

/**
 * Routes
 */
$app->get('/channel', $checkAuth, function() use ($app) {
    $app->process('Channel');
});

$app->get('/channel/new/:type', $checkAuth, function( $type ) use ($app) {
    $app->process('Channel', 'New', array('type' => $type));
});

$app->map('/channel/add(/:clone)', $checkAuth, function( $clone=0 ) use ($app) {
    $app->process('Channel', 'Add', array('clone' => $clone));
})->via('GET', 'POST');

$app->get('/channel/template', $checkAuth, function() use ($app) {
    $app->redirect('/channel/add#template');
});

$app->post('/channel/template', $checkAuth, function() use ($app) {
    $app->process('Channel', 'Template');
});

$app->get('/channel/edit/:id', $checkAuth, function( $id ) use ($app) {
    $app->process('Channel', 'Edit', array('id' => $id));
});

$app->get('/channel/edit/:guid', $checkAuth, function( $guid ) use ($app) {
    $app->process('Channel', 'Edit', array('guid' => $guid));
});

$app->post('/channel/alias', $checkAuth, function() use ($app) {
    $app->process('Channel', 'Alias');
});

$app->post('/channel/edit', $checkAuth, function() use ($app) {
    $app->process('Channel', 'Edit');
});

$app->post('/channel/delete', $checkAuth, function() use ($app) {
    $app->process('Channel', 'Delete');
});
