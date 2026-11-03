<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class PatientAttachmentRequest extends FormRequest
{
    /** Who sees the medical record of the patient may add files to it. */
    public function authorize(): bool
    {
        return Gate::allows('writeRecord', $this->route('patient'));
    }

    /**
     * PDF and images up to 10 MB. "mimes" checks the content of the file, not only its name.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * Messages of the upload.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Escolha um arquivo.',
            'file.mimes' => 'Envie um PDF ou uma imagem (JPG, PNG ou WebP).',
            'file.max' => 'O arquivo pode ter até 10 MB.',
        ];
    }

    /** The uploaded file. */
    public function upload(): UploadedFile
    {
        return $this->file('file');
    }
}
