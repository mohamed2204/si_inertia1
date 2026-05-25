import { useContext, useState } from "react";
import { Checkbox } from "primereact/checkbox";
import { Button } from "primereact/button";
import { Password } from "primereact/password";
import { InputText } from "primereact/inputtext";
import { classNames } from "primereact/utils";
import { router, useForm } from "@inertiajs/react";
// @ts-ignore
import { LayoutContext, LayoutProvider } from "@/Layouts/context/layoutcontext";

const LoginPage = () => {
    const [password, setPassword] = useState("");
    const [checked, setChecked] = useState(false);
    const { layoutConfig } = useContext(LayoutContext);

    // Dans Inertia, on utilise l'objet 'router' directement pour naviguer
    //const { url } = usePage();

    // À l'intérieur de votre composant LoginPage :
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false
    });

    const containerClassName = classNames(
        "surface-ground flex align-items-center justify-content-center min-h-screen min-w-screen overflow-hidden",
        { "p-input-filled": layoutConfig?.inputStyle === "filled" },
    );

    // const handleLogin = () => {
    //     // Logique de redirection Inertia vers la route 'home'
    //     router.visit("/");
    // };

    const submit = (e) => {
        e.preventDefault();
        post('/auth/login'); // Appelle la méthode authenticate du controller
    };

    return (
        <div className={containerClassName}>
            <div className="flex flex-column align-items-center justify-content-center">
                <img
                    src={`/layout/images/logo-${layoutConfig?.colorScheme === "light" ? "dark" : "white"}.svg`}
                    alt="Sakai logo"
                    className="flex-shrink-0 mb-5 w-6rem"
                />
                <div
                    style={{
                        borderRadius: "56px",
                        padding: "0.3rem",
                        background:
                            "linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)",
                    }}
                >
                    <div
                        className="w-full px-5 py-8 surface-card sm:px-8"
                        style={{ borderRadius: "53px" }}
                    >
                        <div className="mb-5 text-center">
                            <img
                                src="/layout/images/login/avatar.png"
                                alt="Image"
                                height="50"
                                className="mb-3"
                            />
                            <div className="mb-3 text-3xl font-medium text-900">
                                Welcome, !
                            </div>
                            <span className="font-medium text-600">
                                Sign in to continue
                            </span>
                        </div>

                        <div>
                            <label
                                htmlFor="email1"
                                className="block mb-2 text-xl font-medium text-900"
                            >
                                Email
                            </label>
                            {/* <InputText
                                id="email1"
                                type="text"
                                placeholder="Email address"
                                className="w-full mb-5 md:w-30rem"
                                style={{ padding: "1rem" }}
                            /> */}
                            <InputText
                                id="email1"
                                type="text"
                                placeholder="Email address"
                                // 1. Liaison de la valeur
                                value={data.email}
                                // 2. Mise à jour automatique de la donnée
                                onChange={(e) => setData('email', e.target.value)}
                                // 3. Style visuel en cas d'erreur (bordure rouge)
                                className={classNames("w-full mb-2 md:w-30rem", { "p-invalid": errors.email })}
                                style={{ padding: "1rem" }}
                            />

                            {/* 4. Affichage du message d'erreur de Laravel */}
                            {errors.email && (
                                <small className="p-error block mb-3">{errors.email}</small>
                            )}
                            <label
                                htmlFor="password1"
                                className="block mb-2 text-xl font-medium text-900"
                            >
                                Password
                            </label>
                            {/* <Password
                                inputId="password1"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="Password"
                                toggleMask
                                className="w-full mb-5"
                                inputClassName="w-full p-3 md:w-30rem"
                            ></Password> */}
                            <Password
                                inputId="password1"
                                // 1. On utilise 'data.password' au lieu de la variable locale
                                value={data.password}
                                // 2. Mise à jour via setData
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Password"
                                toggleMask
                                // 3. Désactive le bandeau de force du mot de passe (souvent inutile sur une page de Login)
                                feedback={false}
                                className="w-full mb-3"
                                // 4. On ajoute 'p-invalid' sur l'input en cas d'erreur
                                inputClassName={classNames("w-full p-3 md:w-30rem", {
                                    'p-invalid': errors.password
                                })}
                            />

                            {/* 5. Affichage du message d'erreur de Laravel */}
                            {errors.password && (
                                <small className="p-error block mb-5">{errors.password}</small>
                            )}
                            <div className="flex gap-5 mb-5 align-items-center justify-content-between">
                                <div className="flex align-items-center">
                                    <Checkbox
                                        inputId="rememberme"
                                        //checked={checked}
                                        // onChange={(e) =>
                                        //     setChecked(e.checked ?? false)
                                        // }
                                        onChange={e => setData('remember', e.checked)}
                                        checked={data.remember}
                                        className="mr-2"
                                    ></Checkbox>
                                    <label htmlFor="rememberme1">
                                        Remember me
                                    </label>
                                </div>
                                <a
                                    className="ml-2 font-medium text-right no-underline cursor-pointer"
                                    style={{ color: "var(--primary-color)" }}
                                >
                                    Forgot password?
                                </a>
                            </div>
                            <Button
                                // label="Sign In"
                                // className="w-full p-3 text-xl"
                                // onClick={handleLogin}
                                label="Sign In"
                                loading={processing} // Affiche un spinner pendant l'envoi
                                onClick={() => post('/auth/login')}
                                className="w-full p-3 text-xl"
                            ></Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

// export default LoginPage;

// Enveloppez le composant pour qu'il ait accès au contexte
const LoginWrapper = (props) => (
    <LayoutProvider>
        <LoginPage {...props} />
    </LayoutProvider>
);

export default LoginWrapper;
