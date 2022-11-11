<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use App\Libs\Response;
use App\Mail\SendEmailVerificationLink;

use App\Models\Signup;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function attempt(Request $request)
    {
        $fields = [
            'username' => $request->username,
            'password' => $request->password
        ];

        $rules = [
            'username' => ['required'],
            'password' => ['required']
        ];

        $validator = Validator::make($fields, $rules);
        $response = new Response();
        if ($validator->fails())
            return $response->json(null, $validator->errors(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);


       if (Auth::attempt($fields)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return$response->json([
                'token' => $token,
                'user' => $user,
            ], 'Login success');
        }

        return $response->json(null, 'Invalid login credentials.', HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $response = new Response();
        return $response->json(null, 'Logout success');
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        $response = new Response();
        return $response->json(null, 'Logout all success');
    }

    public function signup(Request $request)
    {
        // allow csrf token
        $request->headers->set('X-CSRF-TOKEN', $request->cookie('XSRF-TOKEN'));
        $response = new Response();
        $fields = $request->all();

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_website' => ['required', 'string', 'max:255'],
            'number_of_employees' => ['required', 'integer', 'min:0'],
            'capital_raised' => ['required', 'string', 'max:255'],
            'is_generate_revenue' => ['required', 'boolean'],
            'is_profitable' => ['required', 'boolean'],
            'business_description' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,docx,pptx', 'max:10240'],
        ];

        $messages = [
            'full_name.required' => '*name cannot be empty',
            'email.required' => '*email cannot be empty',
            'company_name.required' => '*company name cannot be empty',
            'company_website.required' => '*company website cannot be empty',
            'number_of_employees.required' => '*employees cannot be empty',
            'capital_raised.required' => '*capital you need cannot be empty',
            'is_generate_revenue.required' => '*revenue cannot be empty',
            'is_profitable.required' => '*profit cannot be empty',
            'business_description.required' => '*Business description cannot be empty',

            'full_name.string' => '*name cannot be empty',
            'email.string' => '*email cannot be empty',
            'company_name.string' => '*company name cannot be empty',
            'company_website.string' => '*company website cannot be empty',
            'capital_raised.string' => '*capital you need cannot be empty',
            'business_description.string' => '*Business description cannot be empty',

            'full_name.max' => '*name cannot be more than 255 characters',
            'email.max' => '*email cannot be more than 255 characters',
            'company_name.max' => '*company name cannot be more than 255 characters',
            'company_website.max' => '*company website cannot be more than 255 characters',
            'capital_raised.max' => '*capital you need cannot be more than 255 characters',
            'business_description.max' => '*Business description cannot be more than 255 characters',

            'email.email' => '*email is not valid',
            'number_of_employees.integer' => '*employees must be a number',

            'file.mimes' => '*file must be pdf, docx, pptx',
            'file.max' => '*file cannot be more than 10MB',
        ];

        $validator = Validator::make($fields, $rules, $messages);

        if ($validator->fails())
            return $response->json(null, $validator->errors(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);

        // save file
        $file = $request->file('file');
        $file_name = null;
        if ($file) {
            $file_name = Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/files', $file_name);
        }

        DB::beginTransaction();
        try {
            $signup = Signup::create([
                'full_name' => $fields['full_name'],
                'email' => $fields['email'],
                'company_name' => $fields['company_name'],
                'company_website' => $fields['company_website'],
                'number_of_employees' => $fields['number_of_employees'],
                'capital_raised' => $fields['capital_raised'],
                'is_generate_revenue' => $fields['is_generate_revenue'],
                'is_profitable' => $fields['is_profitable'],
                'business_description' => $fields['business_description'],
                'file' => 'files/'. $file_name,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $response->json(null, $e->getMessage(), HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response->json($signup->toArray(), 'Register success');
    }
}
