import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/utils/validators.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../widgets/common/custom_text_field.dart';
import '../../widgets/common/loading_button.dart';
import '../../widgets/common/custom_snackbar.dart';

class ForgotPasswordPage extends StatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  State<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends State<ForgotPasswordPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();

  bool _isLoading = false;
  bool _emailSent = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Reset Password'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/login'),
        ),
      ),
      body: SafeArea(
        child: BlocListener<AuthBloc, AuthState>(
          listener: (context, state) {
            setState(() {
              _isLoading = state is AuthLoading;
            });

            if (state is ForgotPasswordSuccess) {
              setState(() {
                _emailSent = true;
              });
              CustomSnackBar.showSuccess(
                context: context,
                message: 'Password reset instructions sent to your email.',
              );
            } else if (state is AuthError) {
              CustomSnackBar.showError(
                context: context,
                message: state.message,
              );
            }
          },
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppConstants.defaultPadding),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 32),

                  // Icon
                  Icon(
                    Icons.lock_reset_outlined,
                    size: 80,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(height: 32),

                  if (!_emailSent) ...[
                    // Initial Form
                    Text(
                      'Forgot Password?',
                      style: Theme.of(context).textTheme.displaySmall,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),

                    Text(
                      'Don\'t worry! Enter your email address and we\'ll send you instructions to reset your password.',
                      style: Theme.of(context).textTheme.bodyLarge,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 40),

                    // Email Field
                    CustomTextField(
                      controller: _emailController,
                      label: 'Email Address',
                      keyboardType: TextInputType.emailAddress,
                      prefixIcon: Icons.email_outlined,
                      validator: Validators.validateEmail,
                      enabled: !_isLoading,
                      textInputAction: TextInputAction.done,
                      onFieldSubmitted: (_) => _handleSendResetEmail(),
                    ),
                    const SizedBox(height: 24),

                    // Send Reset Email Button
                    LoadingButton(
                      onPressed: _handleSendResetEmail,
                      isLoading: _isLoading,
                      child: const Text('Send Reset Instructions'),
                    ),
                  ] else ...[
                    // Success State
                    Text(
                      'Check Your Email',
                      style: Theme.of(context).textTheme.displaySmall,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),

                    Text(
                      'We\'ve sent password reset instructions to:',
                      style: Theme.of(context).textTheme.bodyLarge,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),

                    Text(
                      _emailController.text.trim(),
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        color: Theme.of(context).colorScheme.primary,
                        fontWeight: FontWeight.w600,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 24),

                    Text(
                      'Please check your email and follow the instructions to reset your password. The link will expire in 1 hour.',
                      style: Theme.of(context).textTheme.bodyMedium,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 40),

                    // Resend Email Button
                    LoadingButton(
                      onPressed: _handleSendResetEmail,
                      isLoading: _isLoading,
                      isOutlined: true,
                      child: const Text('Resend Email'),
                    ),
                  ],

                  const SizedBox(height: 32),

                  // Back to Login
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Remember your password? ',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      TextButton(
                        onPressed: _isLoading ? null : () {
                          context.go('/login');
                        },
                        child: const Text('Back to Sign In'),
                      ),
                    ],
                  ),

                  if (!_emailSent) ...[
                    const SizedBox(height: 24),

                    // Help Text
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surfaceVariant,
                        borderRadius: BorderRadius.circular(AppConstants.borderRadius),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(
                                Icons.info_outline,
                                size: 20,
                                color: Theme.of(context).colorScheme.primary,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                'Need Help?',
                                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  color: Theme.of(context).colorScheme.primary,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '• Make sure to check your spam/junk folder\n'
                            '• The reset link expires after 1 hour\n'
                            '• Contact support if you continue having issues',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _handleSendResetEmail() {
    if (_formKey.currentState?.validate() ?? false) {
      final email = _emailController.text.trim();

      context.read<AuthBloc>().add(
        ForgotPasswordRequested(email: email),
      );
    }
  }
}