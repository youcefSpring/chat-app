import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_constants.dart';
import '../../bloc/auth/auth_bloc.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  int _selectedIndex = 0;

  final List<_NavigationItem> _navigationItems = [
    _NavigationItem(
      icon: Icons.chat_bubble_outline,
      selectedIcon: Icons.chat_bubble,
      label: 'Chats',
    ),
    _NavigationItem(
      icon: Icons.tag_outlined,
      selectedIcon: Icons.tag,
      label: 'Channels',
    ),
    _NavigationItem(
      icon: Icons.call_outlined,
      selectedIcon: Icons.call,
      label: 'Calls',
    ),
    _NavigationItem(
      icon: Icons.settings_outlined,
      selectedIcon: Icons.settings,
      label: 'Settings',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is AuthUnauthenticated) {
          context.go('/login');
        }
      },
      child: Scaffold(
        body: _buildBody(),
        bottomNavigationBar: _buildBottomNavigationBar(),
      ),
    );
  }

  Widget _buildBody() {
    switch (_selectedIndex) {
      case 0:
        return _buildChatsTab();
      case 1:
        return _buildChannelsTab();
      case 2:
        return _buildCallsTab();
      case 3:
        return _buildSettingsTab();
      default:
        return _buildChatsTab();
    }
  }

  Widget _buildBottomNavigationBar() {
    return BottomNavigationBar(
      type: BottomNavigationBarType.fixed,
      currentIndex: _selectedIndex,
      onTap: (index) {
        setState(() {
          _selectedIndex = index;
        });
      },
      items: _navigationItems.map((item) {
        final isSelected = _navigationItems.indexOf(item) == _selectedIndex;
        return BottomNavigationBarItem(
          icon: Icon(isSelected ? item.selectedIcon : item.icon),
          label: item.label,
        );
      }).toList(),
    );
  }

  Widget _buildChatsTab() {
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          title: const Text('Chats'),
          floating: true,
          actions: [
            IconButton(
              icon: const Icon(Icons.search),
              onPressed: () {
                // TODO: Implement search
              },
            ),
            IconButton(
              icon: const Icon(Icons.add),
              onPressed: () {
                // TODO: Implement new chat
              },
            ),
          ],
        ),
        SliverPadding(
          padding: const EdgeInsets.all(AppConstants.defaultPadding),
          sliver: SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) {
                return _buildChatItem(index);
              },
              childCount: 10, // Placeholder count
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildChannelsTab() {
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          title: const Text('Channels'),
          floating: true,
          actions: [
            IconButton(
              icon: const Icon(Icons.search),
              onPressed: () {
                // TODO: Implement search
              },
            ),
            IconButton(
              icon: const Icon(Icons.add),
              onPressed: () {
                context.go('/home/channels');
              },
            ),
          ],
        ),
        SliverPadding(
          padding: const EdgeInsets.all(AppConstants.defaultPadding),
          sliver: SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) {
                return _buildChannelItem(index);
              },
              childCount: 5, // Placeholder count
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCallsTab() {
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          title: const Text('Calls'),
          floating: true,
          actions: [
            IconButton(
              icon: const Icon(Icons.search),
              onPressed: () {
                // TODO: Implement search
              },
            ),
          ],
        ),
        SliverPadding(
          padding: const EdgeInsets.all(AppConstants.defaultPadding),
          sliver: SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) {
                return _buildCallItem(index);
              },
              childCount: 3, // Placeholder count
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSettingsTab() {
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          title: const Text('Settings'),
          floating: true,
        ),
        SliverPadding(
          padding: const EdgeInsets.all(AppConstants.defaultPadding),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              _buildUserProfile(),
              const SizedBox(height: 24),
              _buildSettingsSection(),
            ]),
          ),
        ),
      ],
    );
  }

  Widget _buildChatItem(int index) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppConstants.smallPadding),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.primary,
          child: Text('U$index'),
        ),
        title: Text('User $index'),
        subtitle: Text('Last message preview...'),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              '12:30 PM',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '2',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ),
        onTap: () {
          context.go('/home/chat/user-$index');
        },
      ),
    );
  }

  Widget _buildChannelItem(int index) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppConstants.smallPadding),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.secondary,
          child: const Icon(Icons.tag, color: Colors.white),
        ),
        title: Text('Channel $index'),
        subtitle: Text('Channel description...'),
        trailing: Text(
          '25 members',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        onTap: () {
          context.go('/home/chat/channel-$index');
        },
      ),
    );
  }

  Widget _buildCallItem(int index) {
    final isVideoCall = index % 2 == 0;
    final isIncoming = index % 3 == 0;

    return Card(
      margin: const EdgeInsets.only(bottom: AppConstants.smallPadding),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.primary,
          child: Text('U$index'),
        ),
        title: Text('User $index'),
        subtitle: Row(
          children: [
            Icon(
              isIncoming ? Icons.call_received : Icons.call_made,
              size: 16,
              color: isIncoming ? Colors.green : Colors.blue,
            ),
            const SizedBox(width: 4),
            Text(isVideoCall ? 'Video call' : 'Voice call'),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              'Yesterday',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 4),
            Text(
              '5 min',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
        onTap: () {
          // TODO: Show call details or start new call
        },
      ),
    );
  }

  Widget _buildUserProfile() {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        if (state is AuthAuthenticated) {
          final user = state.user;
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(AppConstants.defaultPadding),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    child: user.avatar != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(30),
                            child: Image.network(
                              user.avatar!,
                              width: 60,
                              height: 60,
                              fit: BoxFit.cover,
                            ),
                          )
                        : Text(
                            user.initials,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user.displayName,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        Text(
                          user.email,
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Container(
                              width: 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: _getStatusColor(user.presenceStatus),
                                shape: BoxShape.circle,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              user.presenceStatus.toUpperCase(),
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.edit),
                    onPressed: () {
                      context.go('/home/settings');
                    },
                  ),
                ],
              ),
            ),
          );
        }
        return const SizedBox.shrink();
      },
    );
  }

  Widget _buildSettingsSection() {
    return Column(
      children: [
        _buildSettingsItem(
          icon: Icons.person_outline,
          title: 'Profile',
          subtitle: 'Update your profile information',
          onTap: () {
            context.go('/home/settings');
          },
        ),
        _buildSettingsItem(
          icon: Icons.notifications_outline,
          title: 'Notifications',
          subtitle: 'Manage notification preferences',
          onTap: () {
            context.go('/home/settings');
          },
        ),
        _buildSettingsItem(
          icon: Icons.security_outlined,
          title: 'Privacy & Security',
          subtitle: 'Password and security settings',
          onTap: () {
            context.go('/home/settings');
          },
        ),
        _buildSettingsItem(
          icon: Icons.help_outline,
          title: 'Help & Support',
          subtitle: 'Get help and contact support',
          onTap: () {
            // TODO: Implement help
          },
        ),
        _buildSettingsItem(
          icon: Icons.logout,
          title: 'Sign Out',
          subtitle: 'Sign out of your account',
          onTap: () {
            _showLogoutDialog();
          },
          isDestructive: true,
        ),
      ],
    );
  }

  Widget _buildSettingsItem({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool isDestructive = false,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppConstants.smallPadding),
      child: ListTile(
        leading: Icon(
          icon,
          color: isDestructive ? Colors.red : null,
        ),
        title: Text(
          title,
          style: isDestructive
              ? Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.red)
              : null,
        ),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'online':
        return Colors.green;
      case 'away':
        return Colors.orange;
      case 'dnd':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  void _showLogoutDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sign Out'),
        content: const Text('Are you sure you want to sign out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              context.read<AuthBloc>().add(LogoutRequested());
            },
            child: const Text('Sign Out'),
          ),
        ],
      ),
    );
  }
}

class _NavigationItem {
  final IconData icon;
  final IconData selectedIcon;
  final String label;

  _NavigationItem({
    required this.icon,
    required this.selectedIcon,
    required this.label,
  });
}