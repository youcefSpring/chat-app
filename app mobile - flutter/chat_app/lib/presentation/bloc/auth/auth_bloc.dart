import 'package:bloc/bloc.dart';
import 'package:equatable/equatable.dart';
import '../../../domain/entities/auth_result.dart';
import '../../../domain/entities/user.dart';
import '../../../domain/usecases/auth/login_usecase.dart';
import '../../../domain/usecases/auth/register_usecase.dart';
import '../../../domain/repositories/auth_repository.dart';
import '../../../core/errors/failures.dart';

part 'auth_event.dart';
part 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  final LoginUseCase loginUseCase;
  final RegisterUseCase registerUseCase;
  final AuthRepository authRepository;

  AuthBloc({
    required this.loginUseCase,
    required this.registerUseCase,
    required this.authRepository,
  }) : super(AuthInitial()) {
    on<AuthCheckRequested>(_onAuthCheckRequested);
    on<LoginRequested>(_onLoginRequested);
    on<RegisterRequested>(_onRegisterRequested);
    on<LogoutRequested>(_onLogoutRequested);
    on<ForgotPasswordRequested>(_onForgotPasswordRequested);
    on<ResetPasswordRequested>(_onResetPasswordRequested);
    on<ChangePasswordRequested>(_onChangePasswordRequested);
    on<UpdateProfileRequested>(_onUpdateProfileRequested);
    on<UpdateAvatarRequested>(_onUpdateAvatarRequested);
    on<UpdatePresenceRequested>(_onUpdatePresenceRequested);
    on<RefreshTokenRequested>(_onRefreshTokenRequested);
  }

  Future<void> _onAuthCheckRequested(
    AuthCheckRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    try {
      final isAuthenticated = await authRepository.isAuthenticated();

      if (isAuthenticated) {
        final result = await authRepository.getCurrentUser();
        result.fold(
          (failure) => emit(AuthUnauthenticated()),
          (user) => emit(AuthAuthenticated(user: user)),
        );
      } else {
        emit(AuthUnauthenticated());
      }
    } catch (e) {
      emit(AuthUnauthenticated());
    }
  }

  Future<void> _onLoginRequested(
    LoginRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await loginUseCase(LoginParams(
      email: event.email,
      password: event.password,
      twoFactorCode: event.twoFactorCode,
    ));

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (authResult) => emit(AuthAuthenticated(user: authResult.user)),
    );
  }

  Future<void> _onRegisterRequested(
    RegisterRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await registerUseCase(RegisterParams(
      name: event.name,
      email: event.email,
      password: event.password,
      passwordConfirmation: event.passwordConfirmation,
      organizationName: event.organizationName,
    ));

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (authResult) => emit(AuthAuthenticated(user: authResult.user)),
    );
  }

  Future<void> _onLogoutRequested(
    LogoutRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await authRepository.logout();

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (_) => emit(AuthUnauthenticated()),
    );
  }

  Future<void> _onForgotPasswordRequested(
    ForgotPasswordRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await authRepository.forgotPassword(email: event.email);

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (_) => emit(ForgotPasswordSuccess()),
    );
  }

  Future<void> _onResetPasswordRequested(
    ResetPasswordRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await authRepository.resetPassword(
      token: event.token,
      email: event.email,
      password: event.password,
      passwordConfirmation: event.passwordConfirmation,
    );

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (_) => emit(ResetPasswordSuccess()),
    );
  }

  Future<void> _onChangePasswordRequested(
    ChangePasswordRequested event,
    Emitter<AuthState> emit,
  ) async {
    if (state is! AuthAuthenticated) return;

    emit(AuthLoading());

    final result = await authRepository.changePassword(
      currentPassword: event.currentPassword,
      newPassword: event.newPassword,
      passwordConfirmation: event.passwordConfirmation,
    );

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (_) => emit(PasswordChangeSuccess(
        user: (state as AuthAuthenticated).user,
      )),
    );
  }

  Future<void> _onUpdateProfileRequested(
    UpdateProfileRequested event,
    Emitter<AuthState> emit,
  ) async {
    if (state is! AuthAuthenticated) return;

    emit(AuthLoading());

    final result = await authRepository.updateProfile(
      name: event.name,
      email: event.email,
      phone: event.phone,
      bio: event.bio,
      statusMessage: event.statusMessage,
      timezone: event.timezone,
    );

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (user) => emit(ProfileUpdateSuccess(user: user)),
    );
  }

  Future<void> _onUpdateAvatarRequested(
    UpdateAvatarRequested event,
    Emitter<AuthState> emit,
  ) async {
    if (state is! AuthAuthenticated) return;

    emit(AuthLoading());

    final result = await authRepository.updateAvatar(imagePath: event.imagePath);

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (user) => emit(ProfileUpdateSuccess(user: user)),
    );
  }

  Future<void> _onUpdatePresenceRequested(
    UpdatePresenceRequested event,
    Emitter<AuthState> emit,
  ) async {
    if (state is! AuthAuthenticated) return;

    final result = await authRepository.updatePresenceStatus(status: event.status);

    result.fold(
      (failure) => emit(AuthError(
        message: FailureHelper.getDisplayMessage(failure),
        failure: failure,
      )),
      (_) {
        final currentUser = (state as AuthAuthenticated).user;
        final updatedUser = currentUser.copyWith(presenceStatus: event.status);
        emit(AuthAuthenticated(user: updatedUser));
      },
    );
  }

  Future<void> _onRefreshTokenRequested(
    RefreshTokenRequested event,
    Emitter<AuthState> emit,
  ) async {
    final result = await authRepository.refreshToken();

    result.fold(
      (failure) => emit(AuthUnauthenticated()),
      (authResult) => emit(AuthAuthenticated(user: authResult.user)),
    );
  }
}