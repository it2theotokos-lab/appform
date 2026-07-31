<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\OrgUnit;

class OrgStructureController extends Controller {

    /**
     * GET /admin/users/organization
     */
    public function index(): void {
        $tree        = OrgUnit::getTree();
        $departments = OrgUnit::getDepartments();

        View::render('users/organization', [
            'title'       => __('Organizational Structure'),
            'tree'        => $tree,
            'departments' => $departments
        ]);
    }

    /**
     * POST /admin/organization/units
     */
    public function store(): void {
        $this->checkCsrf();

        $name     = trim($_POST['name'] ?? '');
        $type     = trim($_POST['type'] ?? 'department');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if (empty($name)) {
            Session::flash('error', __('Unit name is required.'));
            $this->redirect('/admin/users/organization');
            return;
        }

        $res = OrgUnit::create($name, $type, $parentId);
        if ($res['success']) {
            Session::flash('success', $res['message']);
        } else {
            Session::flash('error', $res['message']);
        }

        $this->redirect('/admin/users/organization');
    }

    /**
     * POST /admin/organization/units/{id}/update
     */
    public function update(array $params): void {
        $this->checkCsrf();

        $id       = (int)($params['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if (empty($name)) {
            Session::flash('error', __('Unit name is required.'));
            $this->redirect('/admin/users/organization');
            return;
        }

        $res = OrgUnit::update($id, $name, $parentId);
        if ($res['success']) {
            Session::flash('success', $res['message']);
        } else {
            Session::flash('error', $res['message']);
        }

        $this->redirect('/admin/users/organization');
    }

    /**
     * POST /admin/organization/units/{id}/delete
     */
    public function destroy(array $params): void {
        $this->checkCsrf();

        $id  = (int)($params['id'] ?? 0);
        $res = OrgUnit::delete($id);

        if ($res['success']) {
            Session::flash('success', $res['message']);
        } else {
            Session::flash('error', $res['message']);
        }

        $this->redirect('/admin/users/organization');
    }
}
