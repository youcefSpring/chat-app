import 'package:dartz/dartz.dart';
import '../../../core/errors/failures.dart';
import '../../../core/utils/validators.dart';
import '../../entities/auth_result.dart';
import '../../repositories/auth_repository.dart';

class RegisterUseCase {
  final AuthRepository repository;

  RegisterUseCase(this.repository);

  Future<Either<Failure, AuthResult>> call(RegisterParams params) async {
    // Validate inputs
    final nameValidation = Validators.validateName(params.name);
    if (nameValidation != null) {
      return Left(ValidationFailure(message: nameValidation));
    }

    final emailValidation = Validators.validateEmail(params.email);
    if (emailValidation != null) {
      return Left(ValidationFailure(message: emailValidation));
    }

    final passwordValidation = Validators.validatePassword(params.password);
    if (passwordValidation != null) {
      return Left(ValidationFailure(message: passwordValidation));
    }

    final confirmPasswordValidation = Validators.validateConfirmPassword(
      params.passwordConfirmation,
      params.password,
    );
    if (confirmPasswordValidation != null) {
      return Left(ValidationFailure(message: confirmPasswordValidation));
    }

    final organizationValidation = Validators.validateOrganizationName(params.organizationName);
    if (organizationValidation != null) {
      return Left(ValidationFailure(message: organizationValidation));
    }

    // Call repository
    return await repository.register(
      name: params.name,
      email: params.email,
      password: params.password,
      organizationName: params.organizationName,
    );
  }
}

class RegisterParams {
  final String name;
  final String email;
  final String password;
  final String passwordConfirmation;
  final String organizationName;

  const RegisterParams({
    required this.name,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    required this.organizationName,
  });
}