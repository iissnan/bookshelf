<?php
    session_start();
    header("Content-Type: text/html; charset=utf-8");
    require_once("require.global.php");
    is_login() and header("location: index.php");
    require_once(MODEL_DIR . "/user.class.php");
    require_once(MODEL_DIR . "/invitation.class.php");

    $is_first_user = !empty($_SESSION["first_user"]);

    if (isset($_POST["submitted"]) && $_POST["submitted"] == "yes") {
        $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
        $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";
        $re_password = isset($_POST["re-password"]) ? trim($_POST["re-password"]) : "";
        $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
        $invitation = isset($_POST["username"]) ? trim($_POST["invitation"]) : "";

        $error = false;

        if ($username == "") {
            $error = true;
            $alert->add_message("用户名不能为空<br />");
        } else if (!preg_match("/[-\w\.]+@(?:[a-zA-Z0-9]+\.)*[a-zA-Z0-9]+/", $email)) {
            $error = true;
            $alert->add_message("登录邮箱不正确<br />");
        } else if (!preg_match("/[-_a-zA-Z0-9\.#,]{6,}/", $password)) {
            $error = true;
            $alert->add_message("登录密码不正确<br />");
        } else if ($password != $re_password) {
            $error = true;
            $alert->add_message("确认密码不匹配<br />");
        } else if ($invitation == "" && !$is_first_user) { // 安装时首次注册不进行邀请码校验
            $error = true;
            $alert->add_message("请输入邀请码<br />");
        }

        if (!$error) {
            $user_model = new UserModel();
            $new_user  = array($email, $password, $username, $invitation);

            // 提升第一个注册的用户为管理员权限
            $is_first_user and array_push($new_user, "admin");
            $result = $user_model->add($new_user);
            if ($result) {
                // 生成此用户的邀请码
                $user_id = $user_model->get_last_id();
                $invitation_model = new InvitationModel();
                $new_invitation = array("value" => uniqid(), "user_id" => $user_id);
                $invitation_model->insert($new_invitation)->execute();

                if ($is_first_user) {
                    unset($_SESSION["first_user"]);
                    header("location: login.php?s=install&code=1");
                } else {
                    header("location: login.php?s=reg&code=1");
                }
            } else {
                $error = true;
                $alert->set_message("注册失败了:<br />" . $user_model->get_last_error())->show();
            }
        } else {
            $alert->show();
            $smarty->assign("alert", $alert);
            $smarty->assign("email", $email);
            $smarty->assign("username", $username);
            $smarty->assign("invitation", $invitation);
        }
    }

    $smarty->assign("alert", $alert);
    $smarty->assign("page_title", "帐号注册");
    $smarty->assign("page_class", "register");
    $smarty->assign("is_first_user", $is_first_user);
    $smarty->display("register.tpl");

    isset($user_model) and $user_model->release();