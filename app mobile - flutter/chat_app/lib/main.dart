import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:firebase_core/firebase_core.dart';

import 'core/constants/app_constants.dart';
import 'core/storage/token_storage.dart';
import 'core/network/dio_client.dart';
import 'core/network/api_client.dart';
import 'data/repositories/auth_repository_impl.dart';
import 'domain/usecases/auth/login_usecase.dart';
import 'domain/usecases/auth/register_usecase.dart';
import 'presentation/bloc/auth/auth_bloc.dart';
import 'config/routes/app_router.dart';
import 'config/themes/app_theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase
  await Firebase.initializeApp();

  // Initialize Hive
  await Hive.initFlutter();

  // Initialize Token Storage
  await TokenStorage.instance.init();

  runApp(ChatApp());
}

class ChatApp extends StatelessWidget {
  ChatApp({super.key});

  final _tokenStorage = TokenStorage.instance;
  late final _dioClient = DioClient(_tokenStorage);
  late final _apiClient = ApiClient(_dioClient.dio);
  late final _authRepository = AuthRepositoryImpl(
    apiClient: _apiClient,
    tokenStorage: _tokenStorage,
  );

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<AuthBloc>(
          create: (context) => AuthBloc(
            loginUseCase: LoginUseCase(_authRepository),
            registerUseCase: RegisterUseCase(_authRepository),
            authRepository: _authRepository,
          )..add(AuthCheckRequested()),
        ),
      ],
      child: MaterialApp.router(
        title: AppConstants.appName,
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        darkTheme: AppTheme.darkTheme,
        themeMode: ThemeMode.system,
        routerConfig: AppRouter.router,
      ),
    );
  }
}