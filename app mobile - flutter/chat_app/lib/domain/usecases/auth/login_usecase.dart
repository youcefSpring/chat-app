import 'package:dartz/dartz.dart';
import '../../../core/errors/failures.dart';
import '../../../core/utils/validators.dart';
import '../../entities/auth_result.dart';
import '../../repositories/auth_repository.dart';

class LoginUseCase {
  final AuthRepository repository;

  LoginUseCase(this.repository);

  Future<Either<Failure, AuthResult>> call(LoginParams params) async {
    // Validate inputs
    final emailValidation = Validators.validateEmail(params.email);
    if (emailValidation != null) {
      return Left(ValidationFailure(message: emailValidation));
    }

    final passwordValidation = Validators.validateRequired(params.password, 'Password');
    if (passwordValidation != null) {
      return Left(ValidationFailure(message: passwordValidation));
    }

    // Call repository
    return await repository.login(
      email: params.email,
      password: params.password,
      twoFactorCode: params.twoFactorCode,
    );
  }
}

class LoginParams {
  final String email;
  final String password;
  final String? twoFactorCode;

  const LoginParams({
    required this.email,
    required this.password,
    this.twoFactorCode,
  });
}