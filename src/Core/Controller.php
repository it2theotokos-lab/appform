<?php
namespace App\Core;

abstract class Controller {
    protected function validate(array $data, array $rules): array {
        $validator = new Validator($data);
        $validator->validate($rules);
        if (!$validator->passed()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            $this->back();
        }
        return $validator->validated();
    }

    protected function back() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function redirect(string $url) {
        header("Location: $url");
        exit;
    }

    protected function checkCsrf() {
        if (!Csrf::validate()) {
            http_response_code(419);
            View::render('errors/419');
            exit;
        }
    }
}
