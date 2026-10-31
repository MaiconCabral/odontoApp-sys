import { workspaceMock } from '~/mocks/workspace'

export function useWorkspace() {
  return useState('workspace', () => workspaceMock)
}
