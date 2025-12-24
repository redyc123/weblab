@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Профиль пользователя</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Информация о пользователе</h5>
                        <p><strong>Имя:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Имя пользователя:</strong> {{ $user->username }}</p>
                        <p><strong>Администратор:</strong> {{ $user->is_admin ? 'Да' : 'Нет' }}</p>
                    </div>

                    <div class="mb-4">
                        <h5>API токены</h5>
                        <p>Здесь вы можете создать и управлять вашими API токенами для доступа к REST API.</p>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6>Создать новый токен</h6>
                                <form id="createTokenForm">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="tokenName">Название токена</label>
                                        <input type="text" class="form-control" id="tokenName" name="name" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Создать токен</button>
                                </form>
                                <div id="tokenResult" class="mt-3" style="display: none;">
                                    <div class="alert alert-success">
                                        <strong>Ваш токен:</strong> <span id="newToken" class="font-monospace"></span>
                                        <p class="mb-0"><small class="text-muted">Скопируйте токен сейчас, вы не сможете увидеть его снова!</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h6>Ваши токены</h6>
                                @if($tokens->count() > 0)
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Название</th>
                                                <th>Создан</th>
                                                <th>Последнее использование</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tokens as $token)
                                            <tr>
                                                <td>{{ $token->name }}</td>
                                                <td>{{ \Carbon\Carbon::parse($token->created_at)->format('d.m.Y H:i:s') }}</td>
                                                <td>{{ $token->last_used_at ? \Carbon\Carbon::parse($token->last_used_at)->format('d.m.Y H:i:s') : 'Не использовался' }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger delete-token" data-token-id="{{ $token->id }}">Удалить</button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p>У вас нет активных токенов.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createTokenForm');
    const tokenResult = document.getElementById('tokenResult');

    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const tokenName = document.getElementById('tokenName').value.trim();
            if (!tokenName) {
                alert('Пожалуйста, введите название токена');
                return;
            }

            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/profile/token', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: tokenName,
                    _token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('newToken').textContent = data.token;
                    tokenResult.style.display = 'block';
                    document.getElementById('tokenName').value = '';
                } else {
                    alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при создании токена: ' + error.message);
            });
        });
    }

    // Handle delete token buttons
    document.querySelectorAll('.delete-token').forEach(button => {
        button.addEventListener('click', function() {
            const tokenId = this.getAttribute('data-token-id');

            if (confirm('Вы уверены, что хотите удалить этот токен?')) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(`/profile/token/${tokenId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table instead of reloading
                        const row = this.closest('tr');
                        if (row) {
                            row.remove();
                        }
                    } else {
                        alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при удалении токена: ' + error.message);
                });
            }
        });
    });
});
</script>
@endsection