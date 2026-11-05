
@php
    $editando = isset($utilizador);

    $roleSeleccionado = old(
        'role_id',
        $editando ? ($roleActual->id ?? '') : ''
    );

    $seleccionadas = old(
        'permissoes',
        $permissoesSeleccionadas ?? []
    );
@endphp

<div class="row">

    <div class="col-md-6 mb-3">
        <label>Nome Completo *</label>

        <input type="text"
               name="name"
               class="form-control"
               value="{{ old('name', $utilizador->name ?? '') }}"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label>Email *</label>

        <input type="email"
               name="email"
               class="form-control"
               value="{{ old('email', $utilizador->email ?? '') }}"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label>Telefone</label>

        <input type="text"
               name="telefone"
               class="form-control"
               value="{{ old('telefone', $utilizador->telefone ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label>Role *</label>

        <select name="role_id"
                id="role_id"
                class="form-control"
                required>

            <option value="">Seleccione uma role</option>

            @foreach($roles as $role)
                <option value="{{ $role->id }}"
                        data-codigo="{{ $role->codigo }}"
                        {{ (string) $roleSeleccionado === (string) $role->id ? 'selected' : '' }}>
                    {{ $role->nome }}
                </option>
            @endforeach

        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label>
            Palavra-passe
            {{ $editando ? '(opcional)' : '*' }}
        </label>

        <input type="password"
               name="password"
               class="form-control"
               {{ $editando ? '' : 'required' }}>
    </div>

    <div class="col-md-6 mb-3">
        <label>Confirmar Palavra-passe</label>

        <input type="password"
               name="password_confirmation"
               class="form-control"
               {{ $editando ? '' : 'required' }}>
    </div>

</div>

<div id="area-permissoes" style="display:none;">

    <hr>

    <h6>Permissões Personalizadas</h6>

    <p class="text-muted">
        Seleccione as permissões que este utilizador poderá executar.
    </p>

    <div class="row">

        @foreach($permissoes->groupBy('modulo') as $modulo => $grupo)

            <div class="col-md-6 mb-3">

                <div class="card border">

                    <div class="card-header">
                        <h6 class="mb-0">{{ $modulo }}</h6>
                    </div>

                    <div class="card-body">

                        @foreach($grupo as $permissao)

                            <div class="form-check mb-2">

                                <input class="form-check-input"
                                       type="checkbox"
                                       name="permissoes[]"
                                       value="{{ $permissao->id }}"
                                       id="permissao_{{ $permissao->id }}"
                                       {{ in_array($permissao->id, $seleccionadas) ? 'checked' : '' }}>

                                <label class="form-check-label"
                                       for="permissao_{{ $permissao->id }}">
                                    {{ $permissao->nome }}
                                </label>

                            </div>

                        @endforeach

                    </div>

                </div>

            </div>

        @endforeach

    </div>

</div>

<div class="mt-3">

    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i>
        {{ $editando ? 'Actualizar Utilizador' : 'Guardar Utilizador' }}
    </button>

    <a href="{{ route('admin.utilizadores.index') }}"
       class="btn btn-secondary">
        Cancelar
    </a>

</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const selectRole = document.getElementById('role_id');
    const area = document.getElementById('area-permissoes');

    function actualizarPermissoes() {

        const option = selectRole.options[selectRole.selectedIndex];

        const codigo = option
            ? option.getAttribute('data-codigo')
            : '';

        area.style.display =
            codigo === 'PERSONALIZADO' ? 'block' : 'none';
    }

    selectRole.addEventListener('change', actualizarPermissoes);

    actualizarPermissoes();
});
</script>
@endpush
