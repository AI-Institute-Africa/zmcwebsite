<div class="pr-rich-editor" data-pr-rich-editor>
    <label class="form-label fw-semibold small" for="{{ $id }}">{{ $label }}@if(!empty($required)) <span class="text-danger">*</span>@endif</label>
    <div class="d-flex flex-wrap align-items-center gap-1 p-2 border rounded-top bg-light" role="toolbar" aria-label="{{ $label }} formatting">
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="bold" title="Bold"><i class="ri-bold"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="italic" title="Italic"><i class="ri-italic"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="underline" title="Underline"><i class="ri-underline"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="justifyLeft" title="Align left"><i class="ri-align-left"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="justifyCenter" title="Align center"><i class="ri-align-center"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="justifyRight" title="Align right"><i class="ri-align-right"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-command="justifyFull" title="Justify"><i class="ri-align-justify"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-editor-image data-upload-url="{{ route('staff.pr.images.upload') }}" data-csrf="{{ csrf_token() }}" title="Insert image"><i class="ri-image-add-line"></i></button>
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="d-none" data-editor-image-input>
        <select class="form-select form-select-sm w-auto" data-editor-select="fontName" aria-label="Font family">
            <option value="Arial">Arial</option><option value="Georgia">Georgia</option><option value="Times New Roman">Times New Roman</option><option value="Verdana">Verdana</option>
        </select>
        <select class="form-select form-select-sm w-auto" data-editor-select="fontSize" aria-label="Font size">
            <option value="2">Small</option><option value="3" selected>Normal</option><option value="4">Large</option><option value="5">Extra large</option><option value="6">Heading</option>
        </select>
        <label class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 mb-0" title="Text color">
            <i class="ri-font-color"></i><input type="color" data-editor-color aria-label="Text color" value="#1e293b" style="width:24px;height:20px;padding:0;border:0;background:transparent;">
        </label>
    </div>
    <div id="{{ $id }}" class="form-control rounded-top-0 pr-rich-content" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ $label }} editor" data-editor-content style="min-height:150px;overflow-y:auto;">{!! \App\Support\NewsBodySanitizer::clean($value ?? '') !!}</div>
    <textarea name="{{ $name }}" class="d-none" data-editor-output @if(!empty($required)) required @endif>{{ $value ?? '' }}</textarea>
</div>
